<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T180 — allow and deny for the five section and enrollment write endpoints
|--------------------------------------------------------------------------
|
| The remaining half of contracts/academic.md's eighteen. Separate file from AcademicEndpointsTest
| because these five all need a section to exist first, and a section needs a term, a branch, a
| course and a programme — a fixture the term and course endpoints have no use for.
|
| ## Every probe here carries `scope.bypass_all`
|
| Deliberate, and the only way these tests measure what they claim to. Four of the five endpoints
| resolve a section through the scoped route binding, which returns **404 before** the policy is
| consulted (research.md R-01, FR-024). A probe without a branch or section claim therefore gets 404
| whether or not it holds the permission — so a "deny" assertion would pass against a route with no
| permission check at all, which is precisely the gap constitution VIII exists to close.
|
| With the scope bypassed, the only difference between the allow case and the deny case is the
| permission, and a 403 means the authorization check fired. The 404-instead-of-403 behaviour itself
| is tested where it belongs, in tests/Scope.
|
*/

/**
 * A user who can see every section but holds exactly these permissions.
 *
 * @param  list<string>  $permissions
 */
function aSectionProbe(array $permissions, string $roleName = 'SECTION_PROBE'): App\Models\User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate($roleName, 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view', 'scope.bypass_all']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles([$roleName]);

    return $user->fresh();
}

/**
 * @return array<string, mixed>
 */
function validSectionPayload(Section $like, string $number = '901'): array
{
    return [
        'term_id' => $like->term_id,
        'branch_id' => $like->branch_id,
        'course_id' => $like->course_id,
        'program_id' => $like->program_id,
        'section_number' => $number,
    ];
}

test('api.v1.sections.store — allow and deny', function (): void {
    $existing = Section::factory()->create();

    test()->actingAs(aSectionProbe(['section.create', 'section.view']))
        ->postJson(route('api.v1.sections.store'), validSectionPayload($existing, '911'))
        ->assertCreated();

    test()->actingAs(aSectionProbe(['section.view']))
        ->postJson(route('api.v1.sections.store'), validSectionPayload($existing, '912'))
        ->assertForbidden();
});

test('api.v1.sections.update — allow and deny', function (): void {
    $section = Section::factory()->create();

    /*
     * The full payload, because PUT is a replacement: StoreSectionRequest is shared between POST
     * and PUT and requires the four identifying columns either way. A partial body here would come
     * back 422 and the test would be measuring validation instead of authorization.
     */
    test()->actingAs(aSectionProbe(['section.edit', 'section.view']))
        ->putJson(route('api.v1.sections.update', $section), validSectionPayload($section, '921'))
        ->assertOk();

    /*
     * `section.create` is not `section.edit`. Asserted explicitly, because the request object is
     * shared between POST and PUT and originally checked `section.create` for both — which refused
     * a registrar allowed to correct a section number and let anyone who could create one rewrite
     * every existing one.
     */
    test()->actingAs(aSectionProbe(['section.create', 'section.view']))
        ->putJson(route('api.v1.sections.update', $section), validSectionPayload($section, '922'))
        ->assertForbidden();

    test()->actingAs(aSectionProbe(['section.view']))
        ->putJson(route('api.v1.sections.update', $section), validSectionPayload($section, '923'))
        ->assertForbidden();
});

test('api.v1.sections.instructor — allow and deny', function (): void {
    $section = Section::factory()->create();
    $instructor = aUser('INSTRUCTOR');

    test()->actingAs(aSectionProbe(['section.change_instructor', 'section.view']))
        ->patchJson(route('api.v1.sections.instructor', $section), ['instructor_id' => $instructor->id])
        ->assertOk();

    /*
     * `section.edit` is NOT enough. Reassignment moves who may open the section from now on, which
     * is a different act from correcting its number — so it has its own permission, and a holder of
     * the broader edit right is refused here on purpose.
     */
    test()->actingAs(aSectionProbe(['section.edit', 'section.view']))
        ->patchJson(route('api.v1.sections.instructor', $section), ['instructor_id' => $instructor->id])
        ->assertForbidden();
});

test('api.v1.sections.enrollments.store — allow and deny', function (): void {
    $section = Section::factory()->create();

    $payload = ['university_id' => '441009001', 'name_ar' => 'طالب اختبار'];

    test()->actingAs(aSectionProbe(['enrollment.create', 'section.view']))
        ->postJson(route('api.v1.sections.enrollments.store', $section), $payload)
        ->assertCreated();

    test()->actingAs(aSectionProbe(['section.view']))
        ->postJson(route('api.v1.sections.enrollments.store', $section), [
            'university_id' => '441009002', 'name_ar' => 'طالب اختبار',
        ])
        ->assertForbidden();
});

test('api.v1.enrollments.withdraw — allow and deny', function (): void {
    $section = Section::factory()->create();

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'active',
    ]);

    /*
     * Deny first, then allow. The order matters here and nowhere else in this file: withdrawing is
     * not idempotent — WithdrawEnrollment answers 409 to a second attempt — so an allow case that
     * ran first would make the deny case indistinguishable from an already-withdrawn conflict.
     */
    test()->actingAs(aSectionProbe(['enrollment.create', 'section.view']))
        ->patchJson(route('api.v1.enrollments.withdraw', $enrollment), ['reason' => 'محاولة بدون صلاحية'])
        ->assertForbidden();

    test()->actingAs(aSectionProbe(['enrollment.withdraw', 'section.view']))
        ->patchJson(route('api.v1.enrollments.withdraw', $enrollment), ['reason' => 'انسحاب نظامي'])
        ->assertOk();
});

test('none of the five answer an unauthenticated caller', function (): void {
    $section = Section::factory()->create();

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'active',
    ]);

    test()->postJson(route('api.v1.sections.store'), validSectionPayload($section, '931'))->assertUnauthorized();
    test()->putJson(route('api.v1.sections.update', $section), [])->assertUnauthorized();
    test()->patchJson(route('api.v1.sections.instructor', $section), [])->assertUnauthorized();
    test()->postJson(route('api.v1.sections.enrollments.store', $section), [])->assertUnauthorized();
    test()->patchJson(route('api.v1.enrollments.withdraw', $enrollment), [])->assertUnauthorized();
});
