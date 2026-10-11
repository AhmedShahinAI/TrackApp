<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T174 — the section key (FR-041)
|--------------------------------------------------------------------------
|
| `(term_id, branch_id, course_id, section_number)` is unique; the number alone is not. Section 214
| of ENG101 in Majmaah and section 214 of ENG101 in Zulfi are different sections, and a system that
| refused the second one would force the registrar to renumber a campus to suit the database.
|
| Both layers are asserted separately. The form request alone is a race — two registrars submitting
| the same number in the same second both pass a `unique` rule — and the index alone is a 500 with
| an engine's error message in it.
|
*/

/**
 * @return array{term: AcademicTerm, branch: Branch, course: Course, program: Program}
 */
function aStructure(): array
{
    $organisation = anOrganisation();

    return [
        'term' => AcademicTerm::query()->first() ?? AcademicTerm::factory()->create(),
        'branch' => Branch::factory()->create(['organization_id' => $organisation->id]),
        'course' => Course::factory()->create(['organization_id' => $organisation->id]),
        'program' => Program::factory()->create(['organization_id' => $organisation->id]),
    ];
}

test('the same number coexists across a differing term, branch or course', function (string $vary): void {
    ['term' => $term, 'branch' => $branch, 'course' => $course, 'program' => $program] = aStructure();

    $first = Section::factory()->create([
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ]);

    $second = match ($vary) {
        'term' => ['term_id' => AcademicTerm::factory()->create()->id],
        'branch' => ['branch_id' => Branch::factory()->create(['organization_id' => anOrganisation()->id])->id],
        'course' => ['course_id' => Course::factory()->create(['organization_id' => anOrganisation()->id])->id],

        // An arm rather than a silent fallthrough. The dataset supplies exactly these three today;
        // a fourth added later would otherwise quietly re-test the term case and report a pass.
        default => throw new InvalidArgumentException("unknown variation [{$vary}]"),
    };

    /*
     * $second first: PHP's `+` keeps the LEFT operand's keys, so writing the base array first meant
     * the override never applied and every case tried to insert a genuine duplicate. The index
     * refused it correctly — the test was wrong, not the schema.
     */
    $created = Section::factory()->create($second + [
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ]);

    expect($created->exists)->toBeTrue()
        ->and($created->section_number)->toBe($first->section_number);
})->with(['term', 'branch', 'course']);

test('a genuine duplicate is refused by the database index itself', function (): void {
    ['term' => $term, 'branch' => $branch, 'course' => $course, 'program' => $program] = aStructure();

    Section::factory()->create([
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ]);

    /*
     * Inserted through the query builder, deliberately: this bypasses the form request entirely, so
     * what refuses the row is the index and nothing else. That is the layer that survives two
     * registrars submitting at the same moment, and the one a `unique` rule cannot replace.
     */
    $duplicate = fn () => DB::table('sections')->insert([
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
        'degree' => 'bachelor', 'activity' => 'lecture', 'status' => 'active',
        'created_by' => aUser('SUPER_ADMIN')->id, 'requires_approval' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    expect($duplicate)->toThrow(QueryException::class);
});

test('the endpoint refuses a duplicate with a readable message, not an engine error', function (): void {
    ['term' => $term, 'branch' => $branch, 'course' => $course, 'program' => $program] = aStructure();

    $admin = aUser('SUPER_ADMIN');

    $payload = [
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ];

    $this->actingAs($admin)->postJson('/api/v1/sections', $payload)->assertCreated();

    $response = $this->actingAs($admin)->postJson('/api/v1/sections', $payload)->assertStatus(422);

    app()->setLocale('ar');
    expect($response->json('errors.section_number.0'))->toBe(__('sections.errors.duplicate_key'));
});

test('a rejected section frees its number for reuse', function (): void {
    ['term' => $term, 'branch' => $branch, 'course' => $course, 'program' => $program] = aStructure();

    $rejected = Section::factory()->create([
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ]);

    $rejected->forceFill(['status' => 'rejected'])->saveQuietly();

    /*
     * FR-176: `approval_key` is a generated column that is NULL for a rejected section, and NULLs
     * do not collide in a unique index. So the number a rejection freed is usable again, which is
     * the behaviour a coordinator expects after refusing a request.
     */
    $reused = Section::factory()->create([
        'term_id' => $term->id, 'branch_id' => $branch->id, 'course_id' => $course->id,
        'program_id' => $program->id, 'section_number' => '214',
    ]);

    expect($reused->exists)->toBeTrue();
});
