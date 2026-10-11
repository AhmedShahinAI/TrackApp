<?php

declare(strict_types=1);

namespace App\Modules\UserManagement\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Shared\Rules\PasswordPolicy;
use App\Modules\UserManagement\Actions\CreateUser;
use App\Modules\UserManagement\Actions\DeactivateUser;
use App\Modules\UserManagement\Actions\ResetPassword;
use App\Modules\UserManagement\Http\Requests\StoreUserRequest;
use App\Modules\UserManagement\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * T122, T123, T124 — the accounts surface.
 *
 * Every response goes through present(), which is the only place a user becomes JSON or view data.
 * One place, because the contract says "never includes any password field" and a per-endpoint
 * payload is a per-endpoint chance to leak one — `$user->toArray()` would include `password` the
 * moment someone removed it from the model's hidden list.
 */
final class UserController
{
    private const MAX_PER_PAGE = 100;

    /** The server-side orders the list offers (see index). */
    private const SORTS = ['name', 'last_login', 'last_login_desc'];

    /**
     * Another organization's account does not exist as far as this caller knows: 404, not 403,
     * which would confirm the id (constitution I). The list is filtered the same way.
     */
    private function ensureSameOrganization(Request $request, User $user): void
    {
        abort_unless((int) $user->organization_id === (int) $request->user()?->organization_id, Response::HTTP_NOT_FOUND);
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeFor($request, 'user.view');

        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ]);

        $users = User::query()
            /*
             * The viewer's organization only. User carries no organization scope, so without this
             * a Super Admin of one college listed every other college's staff and when each last
             * signed in (FR-113) — a cross-tenant read of people and their activity.
             */
            ->where('organization_id', $request->user()?->organization_id)
            ->with(['coordinatorBranches', 'roles'])
            ->withCount('taughtSections')
            ->when($request->filled('role'), fn ($q) => $q->whereHas(
                'roles',
                fn ($r) => $r->where('name', $request->string('role')->toString()),
            ))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas(
                'coordinatorBranches',
                fn ($b) => $b->whereKey($request->integer('branch_id')),
            ))
            ->when($request->filled('is_active'), fn ($q) => $q->where(
                'is_active',
                $request->boolean('is_active'),
            ))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';

                // Across both name languages and the email, because a Super Admin searching for a
                // coordinator does not know which language their name was entered in.
                $q->where(fn ($w) => $w
                    ->where('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            /*
             * Sorted by the server, across every page. The table used to sort «آخر دخول» in the
             * browser, which reorders only the 25 rows on screen — so "who has not signed in for
             * longest" never surfaced the dormant accounts on the other pages, which is the one
             * question that column is read for. `last_login` puts never-signed-in first, then the
             * oldest sign-in; name breaks ties so the order is stable between pages.
             */
            ->when(
                $request->input('sort') === 'last_login',
                fn ($q) => $q->orderByRaw('case when last_login_at is null then 0 else 1 end')->orderBy('last_login_at'),
                fn ($q) => $q->when(
                    $request->input('sort') === 'last_login_desc',
                    fn ($d) => $d->orderByRaw('case when last_login_at is null then 1 else 0 end')->orderByDesc('last_login_at'),
                ),
            )
            ->orderBy('name_ar')
            ->orderBy('id')
            // Clamped: an unbounded per_page loaded every account with its relations and rendered
            // two templates per row in one request.
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $users->getCollection()->map(fn (User $u) => $this->present($u))->all(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ],
            ]);
        }

        return view('users.index', [
            'users' => $users,
            'branches' => Branch::query()->where('organization_id', $request->user()?->organization_id)->orderBy('campus_name_ar')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): JsonResponse|RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated();

        // The creator's organization — never "the first college", and never from the payload,
        // which validated() has already stripped of anything not in the rules.
        $attributes['organization_id'] = (int) $request->user()?->organization_id;

        $user = $createUser->execute($attributes);

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($user)], Response::HTTP_CREATED);
        }

        return redirect()->route('users.index')->with('status', __('users.created'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $this->ensureSameOrganization($request, $user);

        // Only the four editable attributes, taken from validated() rather than from the request, so
        // an extra field in the payload cannot reach the model even if it were fillable.
        $user->fill($request->safe()->only(['name_ar', 'name_en', 'email', 'locale']))->save();

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($user->fresh() ?? $user)]);
        }

        return redirect()->route('users.index')->with('status', __('users.saved'));
    }

    public function deactivate(Request $request, User $user, DeactivateUser $deactivate): JsonResponse|RedirectResponse
    {
        $this->authorizeFor($request, 'user.deactivate');
        $this->ensureSameOrganization($request, $user);

        // 409, not 403: the caller holds the permission, and the request is refused because of what
        // it would do rather than who is asking. A Super Admin locking themselves out of the only
        // account that can create accounts is not recoverable from inside the platform.
        if ($user->is((object) $request->user())) {
            return $this->conflict($request, __('users.deactivate.self'));
        }

        $deactivate->execute($user, $request->input('reason'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->present($user->fresh() ?? $user)]);
        }

        return redirect()->route('users.index')->with('status', __('users.deactivate.done'));
    }

    public function resetPassword(Request $request, User $user, ResetPassword $reset): JsonResponse|RedirectResponse
    {
        $this->authorizeFor($request, 'user.reset_password');
        $this->ensureSameOrganization($request, $user);

        $validated = $request->validate([
            'password' => ['required', 'string', new PasswordPolicy],
        ]);

        $reset->execute($user, (string) $validated['password']);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['reset_at' => now()->toIso8601String()]]);
        }

        return redirect()->route('users.index')->with('status', __('users.reset_password.done'));
    }

    /**
     * The only shape a user takes on the way out. No password field, by construction.
     *
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name_ar' => $user->name_ar,
            'name_en' => $user->name_en,
            'email' => $user->email,
            'locale' => $user->locale,
            'is_active' => $user->is_active,
            'roles' => $user->getRoleNames()->all(),
            'branches' => $user->coordinatorBranches->map(fn (Branch $b) => [
                'id' => $b->id,
                'campus_name_ar' => $b->campus_name_ar,
                'campus_name_en' => $b->campus_name_en,
                'gender' => $b->gender,
            ])->all(),
            'taught_sections_count' => $user->taught_sections_count ?? 0,
            'first_login_at' => $user->first_login_at?->toIso8601String(),

            // FR-113 — rendered as "آخر دخول" on the list.
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function authorizeFor(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission) ?? false, Response::HTTP_FORBIDDEN);
    }

    private function conflict(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return redirect()->route('users.index')->with('error', $message);
    }
}
