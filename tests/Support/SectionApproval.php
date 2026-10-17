<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Amendment 4 — the section approval world (Phase 27)
|--------------------------------------------------------------------------
|
| Two branches, each with a coordinator; an instructor who teaches in the first; a Super Admin; and a
| course in a programme the sections can be created against. Sections are created through the real
| endpoint, so the status a test sees is the one the create action derived, never one a fixture chose.
|
*/

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/**
 * @return array{term: AcademicTerm, branch: Branch, other: Branch, program: Program, course: Course, admin: User, coordinator: User, peer: User, outsider: User, instructor: User}
 */
function approvalWorld(): array
{
    $admin = aUser('SUPER_ADMIN');
    $organisation = anOrganisation();

    $term = AcademicTerm::query()->where('is_current', true)->first() ?? AcademicTerm::factory()->current()->create();
    $branch = Branch::factory()->create(['organization_id' => $organisation->id]);
    $other = Branch::factory()->create(['organization_id' => $organisation->id]);

    $program = Program::factory()->create(['organization_id' => $organisation->id]);
    $course = Course::factory()->create(['organization_id' => $organisation->id]);
    $course->programs()->syncWithoutDetaching([$program->id]);

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    // A second coordinator of the same branch: the peer FR-178 forbids from deciding a coordinator's section.
    $peer = aUser('BRANCH_COORDINATOR');
    $peer->coordinatorBranches()->sync([$branch->id]);

    $outsider = aUser('BRANCH_COORDINATOR');
    $outsider->coordinatorBranches()->sync([$other->id]);

    // An instructor creates only in a branch they teach in (StoreSectionRequest::allowedBranchIds).
    $instructor = aUser('INSTRUCTOR');
    Section::factory()->create(['branch_id' => $branch->id, 'term_id' => $term->id, 'instructor_id' => $instructor->id]);

    return compact('term', 'branch', 'other', 'program', 'course', 'admin', 'coordinator', 'peer', 'outsider', 'instructor');
}

/**
 * The create payload for a section in $branch.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sectionPayload(array $world, array $overrides = []): array
{
    return $overrides + [
        'term_id' => $world['term']->id,
        'branch_id' => $world['branch']->id,
        'course_id' => $world['course']->id,
        'program_id' => $world['program']->id,
        'section_number' => (string) fake()->unique()->numberBetween(10000, 99999),
    ];
}

/**
 * POST /api/v1/sections as $creator.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $overrides
 * @return TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function createSectionAs(User $creator, array $world, array $overrides = []): TestResponse
{
    app('auth')->forgetGuards();

    return test()->actingAs($creator)->postJson('/api/v1/sections', sectionPayload($world, $overrides));
}

/**
 * A section created by $creator through the endpoint, refreshed.
 *
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $overrides
 */
function sectionCreatedBy(User $creator, array $world, array $overrides = []): Section
{
    $id = createSectionAs($creator, $world, $overrides)->assertCreated()->json('data.id');
    app('auth')->forgetGuards();

    // The tests reuse one User object across requests, and its scope ids are memoized; production
    // loads a fresh user per request. Forget them, so the creator now reaches the section they made.
    $creator->forgetScopeCache();

    return Section::query()->withoutGlobalScopes()->findOrFail($id);
}

/**
 * PATCH /api/v1/sections/{id}/approval as $reviewer.
 *
 * @return TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function decideSection(User $reviewer, Section $section, string $decision, ?string $reason = null): TestResponse
{
    app('auth')->forgetGuards();

    return test()->actingAs($reviewer)->patchJson('/api/v1/sections/'.$section->id.'/approval', array_filter(['decision' => $decision, 'reason' => $reason]));
}

/**
 * The approval-related audit actions written for one section, in order.
 *
 * @return list<string>
 */
function sectionApprovalActions(Section $section): array
{
    return Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'Section')->where('entity_id', $section->id)
        ->where('action', 'like', 'section.%')
        ->orderBy('id')->pluck('action')->all();
}
