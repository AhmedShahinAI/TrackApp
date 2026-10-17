<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ImportJob;
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

Route::middleware(['auth', 'active', 'session.auth', 'password.own'])->group(function (): void {
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

        /*
         * FR-181 (Phase 27 review): a rejected section is out of every default count as it is out of every
         * default list, and so are its enrollments, which it keeps but which are not anyone's current
         * enrollment. Pending and returned sections are counted, as the list shows them.
         */
        $notRejected = fn () => Section::query()->withoutGlobalScopes()->where('status', '!=', 'rejected')->select('id');

        $scopedSections = fn () => ($sectionIds === null
            ? Section::query()
            : Section::query()->whereIn('id', $sectionIds))->where('status', '!=', 'rejected');

        $scopedEnrollments = fn () => ($sectionIds === null
            ? SectionEnrollment::query()
            : SectionEnrollment::query()->whereIn('section_id', $sectionIds))->whereIn('section_id', $notRejected());

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
            /*
             * Through the model, not DB::table(). A query-builder count bypasses every global
             * scope, which is how this tile came to show a coordinator the college-wide number of
             * imports while every tile beside it was branch-scoped. ImportJob carries both scope
             * traits, so this counts what the caller can actually open.
             */
            'imports' => $user->can('import.view') ? ImportJob::query()->count() : null,
        ];

        /*
         * US2 — scoped by branch now, not only by permission.
         *
         * This query used to run through DB::table('audit_logs'), with a comment claiming a
         * coordinator saw only their own actions. It did no such thing: a query-builder query
         * carries no global scope, so every `audit.view` holder saw college-wide activity. Going
         * through the model applies AuditLog's branch and section constraints — entries whose
         * entity resolves into the caller's branches, plus their own actions, exactly as
         * contracts/audit.md specifies.
         */
        $recent = $user->can('audit.view')
            ? AuditLog::query()
                ->with('user:id,name_ar,name_en')
                ->orderByDesc('created_at')
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

    // T274 — the interface language, stored on the account.
    Route::get('/profile/preferences', fn () => view('profile.preferences'))->name('profile.preferences');
});

/*
 * The AR/EN switch (FR-074). Outside the auth group on purpose: the sign-in screen carries the
 * switch too, and a guest has to be able to read the login page in their own language before they
 * have an account to store a preference on.
 *
 * SEC-034 (G-17): `active` passes a guest straight through, but stops a deactivated account writing
 * `users.locale` with a session it should no longer have; the throttle bounds a write any visitor can
 * make. 30 a minute is far above any person's use of a two-language switch.
 */
Route::middleware(['web', 'active', 'throttle:30,1'])->post('/locale', LocaleController::class)->name('locale.update');
