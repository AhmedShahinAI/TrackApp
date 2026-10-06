<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use App\Modules\Shared\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home and profile — the role landing page
|--------------------------------------------------------------------------
|
| T129, T130. FR-017: sign-in lands the person on the starting page belonging to their role, and
| each role sees only its own in-scope counts.
|
| The scoping here is explicit `whereIn` on the user's branch ids. From Phase 4 US2 onward the
| global scopes (T144-T146) make that automatic, and these counts become plain `count()` calls —
| this is the one place that does it by hand, because the scopes do not exist yet.
|
*/

Route::middleware(['auth', 'active', 'session.auth'])->group(function (): void {
    Route::get('/home', function () {
        $user = request()->user();

        $branchIds = $user->coordinatorBranchIds();
        $isAdmin = $user->can('scope.bypass_all');

        // A coordinator's sections are the ones in their branches; an instructor's are the ones
        // they teach plus any shared with them; a Super Admin's are all of them.
        $sectionIds = match (true) {
            $isAdmin => null,
            $branchIds !== [] => Section::query()->whereIn('branch_id', $branchIds)->pluck('id')->all(),
            default => $user->accessibleSectionIds(),
        };

        $scopedSections = fn () => $sectionIds === null
            ? Section::query()
            : Section::query()->whereIn('id', $sectionIds);

        $scopedEnrollments = fn () => $sectionIds === null
            ? SectionEnrollment::query()
            : SectionEnrollment::query()->whereIn('section_id', $sectionIds);

        $stats = [
            'branches' => $isAdmin
                ? Branch::query()->count()
                : count($branchIds),
            'sections' => $scopedSections()->count(),
            'students' => $isAdmin
                ? Student::query()->count()
                : $scopedEnrollments()->distinct()->count('student_id'),
            'users' => $user->can('user.view') ? User::query()->count() : null,
            'enrollments' => $scopedEnrollments()->where('status', 'active')->count(),
            'imports' => $user->can('import.view') ? DB::table('import_jobs')->count() : null,
        ];

        // The audit log is scoped by permission, not by branch: a coordinator sees their own
        // actions, a Super Admin sees everything. Branch-scoped audit arrives with US2's scopes.
        $recent = $user->can('audit.view')
            ? DB::table('audit_logs')
                ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
                ->select('audit_logs.action', 'audit_logs.created_at', 'users.name_ar', 'users.name_en')
                ->orderByDesc('audit_logs.created_at')
                ->limit(8)
                ->get()
            : collect();

        return view('home.index', [
            'stats' => array_filter($stats, static fn ($v) => $v !== null),
            'recent' => $recent,
            'currentTerm' => AcademicTerm::query()->where('is_current', true)->first(),
        ]);
    })->name('home');

    Route::get('/profile', fn () => view('profile.index'))->name('profile');
});

/*
 * The AR/EN switch (FR-074). Outside the auth group on purpose: the sign-in screen carries the
 * switch too, and a guest has to be able to read the login page in their own language before they
 * have an account to store a preference on.
 */
Route::middleware('web')->post('/locale', LocaleController::class)->name('locale.update');
