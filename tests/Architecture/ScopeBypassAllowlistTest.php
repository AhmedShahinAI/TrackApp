<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| SEC-056 — every place that steps outside the branch scope is known, and says why (G-22)
|--------------------------------------------------------------------------
|
| Branch and section scoping is carried by global scopes (BranchScope, SectionScope); the policies
| check permissions only. So a `withoutGlobalScope(s)` on a request path is the one line that can
| turn "this coordinator's students" into "every student", and it reads like any other query.
|
| Each site below was reviewed on 2026-10-17 (security plan SEC-055; the site-by-site table is in
| docs/security/SECURITY-EVIDENCE-LOG.md). The count is the number of textual occurrences in the
| file, comments included, so a new bypass anywhere — even a second one in a file already listed —
| fails here until someone reviews it and raises the number with a reason.
|
*/

/** @var array<string, array{0: int, 1: string}> */
const REVIEWED_SCOPE_BYPASSES = [
    'app/Console/Commands/MakeRosterFixture.php' => [1, 'console dev fixture reads first student'],
    'app/Console/Commands/Monitor.php' => [1, 'console retention check, exists only'],
    'app/Console/Commands/PruneImportFiles.php' => [2, 'scheduled file-retention sweep reads every import job (SEC-063)'],
    'app/Models/AuditLog.php' => [1, 'constrainToBranches subquery is the scope definition itself'],
    'app/Models/ImportJob.php' => [2, 'duplicateOf returns an id only; constrainToBranches subquery is the scope definition'],
    'app/Models/SectionEnrollment.php' => [3, 'scope definition subquery plus comment; currentlyHeld status filter narrows only'],
    'app/Models/Student.php' => [3, 'scope definition subqueries through enrollments plus a comment'],
    'app/Models/SupportRequest.php' => [5, 'holdersIn scope definition; studentBranchIds spans branches (candidates re-scoped, SEC-055)'],
    'app/Models/User.php' => [4, 'builds the caller scope ids; unscoped to avoid recursion'],
    'app/Modules/AcademicStructure/Http/Controllers/BranchController.php' => [1, 'instructor branches re-scoped via accessibleSectionIds'],
    'app/Modules/AcademicStructure/Http/Controllers/ProgramController.php' => [1, 'exists check: does any section hold the programme code'],
    'app/Modules/Audit/Http/Controllers/AuditLogController.php' => [1, 'branch filter subquery combined with the AuditLog scope, narrows only'],
    'app/Modules/Authorization/Http/Controllers/SectionAccessGrantController.php' => [1, 'reads organization of an already bound section'],
    'app/Modules/Imports/Actions/StartValidation.php' => [1, 'compare and set on a bound import behind can run'],
    'app/Modules/Imports/Http/Controllers/ImportJobController.php' => [2, 'compare and set commit and resume on a bound import behind can run'],
    'app/Modules/Imports/Http/Controllers/ImportMappingController.php' => [1, 'compare and set mapping on a bound import behind can run'],
    'app/Modules/Imports/Jobs/CommitImportChunkJob.php' => [7, 'queue worker reloads and locks own job; section re-checked via importRoster'],
    'app/Modules/Imports/Jobs/ValidateImportJob.php' => [5, 'queue worker reloads and updates its own import job'],
    'app/Modules/Imports/Pipeline/ResolveRegistrarSection.php' => [1, 'section lookup for registrar import; bypass holders only (ImportJobPolicy, SEC-055)'],
    'app/Modules/Imports/Pipeline/Resolver.php' => [7, 'commit job lookups and enrollment update for the job rows'],
    'app/Modules/Imports/Pipeline/RowValidator.php' => [5, 'validation job existence lookups against unique indexes'],
    'app/Modules/Imports/Policies/ImportJobPolicy.php' => [1, 'loads section of an owned bound job then checks importRoster'],
    'app/Modules/Sections/Actions/ChangeSectionInstructor.php' => [1, 'reads organization of an already bound section'],
    'app/Modules/Sections/Actions/DecideSectionApproval.php' => [1, 'lockForUpdate re-read of the scoped bound section'],
    'app/Modules/Sections/Actions/ResubmitSection.php' => [1, 'lockForUpdate re-read of the scoped bound section'],
    'app/Modules/Sections/Http/Controllers/SectionController.php' => [1, 'comment only: scopes deliberately kept'],
    'app/Modules/Sections/Http/Requests/StoreSectionRequest.php' => [1, 'allowed branches re-scoped to accessibleSectionIds'],
    'app/Modules/Shared/Routes/web.php' => [1, 'not rejected subquery combined with scoped home counts'],
    'app/Modules/Students/Actions/EnrollStudent.php' => [3, 'unique key lookups before enrolling into a scoped bound section'],
    'app/Modules/Support/Actions/AnonymiseExpiredRequests.php' => [2, 'scheduled anonymisation sweep'],
    'app/Modules/Support/Actions/ReassignSupportRequest.php' => [1, 'lockForUpdate re-read of the scoped bound request'],
    'app/Modules/Support/Actions/RespondToSupportRequest.php' => [1, 'lockForUpdate re-read of the scoped bound request'],
    'app/Modules/Support/Actions/TransitionSupportRequest.php' => [1, 'lockForUpdate re-read of the scoped bound request'],
    'app/Modules/Support/Http/Controllers/Public/SupportStatusController.php' => [2, 'public status lookup needs reference plus University ID'],
    'app/Modules/Support/Http/Controllers/SupportQueueController.php' => [2, 'University ID subquery narrows scoped query; branch options re-scoped'],
    'app/Modules/Support/Services/ReferenceGenerator.php' => [1, 'reference uniqueness exists check'],
    'app/Modules/Support/Services/VerifyStudentIdentity.php' => [1, 'public identity verification returns ids only'],
    'app/Providers/RouteServiceProvider.php' => [1, 'comment only: warns that bindings must never drop scopes'],
];

test('every scope bypass in app/ is a reviewed one, with its reason', function (): void {
    $found = [];

    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        $count = substr_count((string) file_get_contents((string) $file->getRealPath()), 'withoutGlobalScope');

        if ($count > 0) {
            $found['app/'.str_replace('\\', '/', $file->getRelativePathname())] = $count;
        }
    }

    ksort($found);
    $reviewed = array_map(static fn (array $entry): int => $entry[0], REVIEWED_SCOPE_BYPASSES);
    ksort($reviewed);

    expect($found)->toBe($reviewed, 'A scope bypass was added, moved or removed. Review it, then update REVIEWED_SCOPE_BYPASSES with its reason.');
});

test('every reviewed bypass carries a reason', function (): void {
    foreach (REVIEWED_SCOPE_BYPASSES as $path => [$count, $reason]) {
        expect($count)->toBeGreaterThan(0, $path)
            ->and(mb_strlen($reason))->toBeGreaterThan(10, $path);
    }
});
