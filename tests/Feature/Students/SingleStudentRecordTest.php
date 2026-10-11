<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use App\Models\Student;

/*
|--------------------------------------------------------------------------
| T176 — one person, one row (FR-043, SC-012)
|--------------------------------------------------------------------------
|
| The university id is the person. A student in four sections is one record with four enrollments,
| not four students who happen to share a number — and the difference shows the first time someone
| asks "has this student withdrawn from anything".
|
*/

/**
 * @return Illuminate\Testing\TestResponse<Illuminate\Http\JsonResponse>
 */
function enrollInto(Section $section, string $universityId, string $name, App\Models\User $actor): Illuminate\Testing\TestResponse
{
    return test()->actingAs($actor)->postJson("/api/v1/sections/{$section->id}/enrollments", [
        'university_id' => $universityId,
        'name_ar' => $name,
    ]);
}

test('enrolling the same id twice links one record, not two', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $first = Section::factory()->create();
    $second = Section::factory()->create();

    enrollInto($first, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();
    enrollInto($second, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();

    expect(Student::query()->where('university_id', '441234567')->count())->toBe(1);

    $student = Student::query()->where('university_id', '441234567')->firstOrFail();
    expect($student->enrollments()->count())->toBe(2);
});

test('arabic-indic digits reach the same person', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $first = Section::factory()->create();
    $second = Section::factory()->create();

    enrollInto($first, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();

    /*
     * The same id typed on an Arabic keyboard. Without normalisation this created a second student
     * who shared the number — the exact duplicate FR-043 exists to prevent, arriving through a
     * validation rule that accepted both forms and stored them differently.
     */
    enrollInto($second, '٤٤١٢٣٤٥٦٧', 'عبدالله القحطاني', $admin)->assertCreated();

    expect(Student::query()->count())->toBe(1)
        ->and(Student::query()->first()->university_id)->toBe('441234567');
});

test('a student appears once in a scoped list despite several enrollments', function (): void {
    $organisation = anOrganisation();
    $branch = Branch::factory()->create(['organization_id' => $organisation->id]);

    $admin = aUser('SUPER_ADMIN');

    $sections = Section::factory()->count(3)->create(['branch_id' => $branch->id]);

    foreach ($sections as $section) {
        enrollInto($section, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();
    }

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$branch->id]);
    $coordinator->forgetScopeCache();

    /*
     * SC-012. The scope reaches students through enrollments, so three enrollments in scope is
     * three ways for the same row to match — `whereExists` rather than a join is what keeps it one
     * line, with no `distinct()` for a future caller to forget.
     */
    $rows = (array) test()->actingAs($coordinator)->getJson('/api/v1/students')->assertOk()->json('data');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['university_id'])->toBe('441234567');
});

test('a name that disagrees is reported, and the stored one stands', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $first = Section::factory()->create();
    $second = Section::factory()->create();

    enrollInto($first, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();

    $response = enrollInto($second, '441234567', 'عبد الله القحطانى', $admin)->assertCreated();

    /*
     * Reported, not applied. Letting the newest spelling win means a typo in one enrollment renames
     * the person across every record they appear in, silently and retroactively.
     */
    expect($response->json('warnings'))->not->toBeEmpty();

    expect(Student::query()->first()->name_ar)->toBe('عبدالله القحطاني');
});

test('enrolling the same student in the same section twice is 409', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();

    enrollInto($section, '441234567', 'عبدالله القحطاني', $admin)->assertCreated();
    enrollInto($section, '441234567', 'عبدالله القحطاني', $admin)->assertStatus(409);

    expect($section->enrollments()->count())->toBe(1);
});
