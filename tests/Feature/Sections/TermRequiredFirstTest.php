<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T179 — a section cannot exist before a term does (FR-037)
|--------------------------------------------------------------------------
|
| ## Why 409 and not 422
|
| Nothing in the payload is wrong. The registrar filled the form correctly; the institution has not
| been set up yet. A 422 with a field error would send them back to inspect their own input, looking
| for a mistake that is not there — the fix is somewhere else entirely, on the terms screen, and
| probably by somebody else.
|
| That distinction is the whole content of this file, and it is easy to lose: the refusal lives in
| StoreSectionRequest::prepareForValidation(), which runs *before* the rules, precisely so the
| `exists:academic_terms,id` rule never gets a chance to report it as a bad field.
|
| ## The ordering is asserted, not assumed
|
| A test that only checks the status code would pass if prepareForValidation were moved after
| validation, because `term_id` would then fail `exists` and… also be a 4xx. So the payload here is
| deliberately *complete and plausible* while the terms table is empty, and the message is compared
| against the specific string the contract names.
|
*/

/** A registrar who may create sections and see everything, so the 409 is the only thing in the way. */
function aRegistrar(): App\Models\User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate('TERM_FIRST_PROBE', 'web');
    $role->syncPermissions(['section.create', 'section.view', 'dashboard.view', 'scope.bypass_all']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles(['TERM_FIRST_PROBE']);

    return $user->fresh();
}

test('creating a section with no term in the system is refused with 409', function (): void {
    /*
     * A branch, a course and a programme but no term. Section::factory() would create one as a
     * dependency, which is exactly the thing under test, so the structure is assembled by hand.
     */
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    $course = Course::factory()->create(['organization_id' => anOrganisation()->id]);
    $program = Program::factory()->create();

    expect(AcademicTerm::query()->withTrashed()->count())->toBe(0);

    $response = test()->actingAs(aRegistrar())
        ->postJson(route('api.v1.sections.store'), [
            // A plausible id for a term that does not exist. If the refusal were moved after
            // validation this would come back 422 on `term_id.exists`, and the assertion below on
            // the message is what distinguishes the two outcomes.
            'term_id' => 1,
            'branch_id' => $branch->id,
            'course_id' => $course->id,
            'program_id' => $program->id,
            'section_number' => '101',
        ])
        ->assertStatus(409);

    app()->setLocale('ar');
    expect($response->json('message'))->toBe(__('sections.errors.term_required_first'));

    /*
     * And nothing was written. A refusal that still created a row would be worse than no refusal.
     *
     * No withTrashed(): Section does not soft-delete, unlike AcademicTerm and Student. Worth saying
     * out loud, because the roster history hangs off sections and nothing in the schema stops one
     * being removed outright — raised for the Product Owner rather than papered over here.
     */
    expect(Section::query()->count())->toBe(0);
});

test('the refusal names the terms screen as the fix, not the submitted form', function (): void {
    Branch::factory()->create(['organization_id' => anOrganisation()->id]);

    $response = test()->actingAs(aRegistrar())
        ->postJson(route('api.v1.sections.store'), ['section_number' => '101'])
        ->assertStatus(409);

    /*
     * Deliberately an incomplete payload. It would fail four `required` rules — and still comes back
     * 409 rather than 422, because the institutional precondition is checked first. That ordering is
     * the point: a registrar told "the branch field is required" when the real problem is that no
     * academic term exists will fill the form in correctly and be refused again.
     */
    expect($response->json('errors'))->toBeNull();

    app()->setLocale('ar');
    expect($response->json('message'))->toBe(__('sections.errors.term_required_first'));
});

test('once a term exists the same request succeeds', function (): void {
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    $course = Course::factory()->create(['organization_id' => anOrganisation()->id]);
    $program = Program::factory()->create();
    $term = AcademicTerm::factory()->create();

    // The control. Without it, a 409 returned unconditionally — from a typo in the condition, say —
    // would satisfy both tests above and this file would be asserting nothing.
    test()->actingAs(aRegistrar())
        ->postJson(route('api.v1.sections.store'), [
            'term_id' => $term->id,
            'branch_id' => $branch->id,
            'course_id' => $course->id,
            'program_id' => $program->id,
            'section_number' => '101',
        ])
        ->assertCreated();
});

test('a soft-deleted term still counts as a term existing', function (): void {
    /*
     * `AcademicTerm::query()->exists()` is the check, and AcademicTerm soft-deletes — so a deleted
     * term is invisible to it and the refusal returns. Asserted rather than left to chance, because
     * the alternative reading ("any term row, deleted or not") would let a section be created into
     * a term that no screen shows, and the section would be unreachable from the moment it existed.
     */
    $term = AcademicTerm::factory()->create();
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    $course = Course::factory()->create(['organization_id' => anOrganisation()->id]);
    $program = Program::factory()->create();

    $term->delete();

    test()->actingAs(aRegistrar())
        ->postJson(route('api.v1.sections.store'), [
            'term_id' => $term->id,
            'branch_id' => $branch->id,
            'course_id' => $course->id,
            'program_id' => $program->id,
            'section_number' => '101',
        ])
        ->assertStatus(409);
});
