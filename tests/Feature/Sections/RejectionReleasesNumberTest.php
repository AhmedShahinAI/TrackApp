<?php

declare(strict_types=1);

use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T477 — a rejection returns the section's number to the pool (FR-176, SC-041)
|--------------------------------------------------------------------------
|
| The generated column `approval_key` is NULL for a rejected section, and NULLs never collide in the
| unique index, so a rejected number is free while two live sections still cannot share one.
|
*/

test('after a rejection, the same term, branch, course and number can be used again', function (): void {
    $world = approvalWorld();
    $rejected = sectionCreatedBy($world['instructor'], $world, ['section_number' => '7001']);
    decideSection($world['coordinator'], $rejected, 'reject', 'This section duplicates another one.')->assertOk();

    createSectionAs($world['instructor'], $world, ['section_number' => '7001'])->assertCreated();
});

test('two non-rejected sections with that key are still refused, pending or active', function (string $first): void {
    $world = approvalWorld();
    createSectionAs($world[$first], $world, ['section_number' => '7002'])->assertCreated();

    createSectionAs($world['instructor'], $world, ['section_number' => '7002'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('section_number');
})->with(['an active one' => ['admin'], 'a pending one' => ['instructor']]);

test('several rejected sections with one number coexist', function (): void {
    $world = approvalWorld();

    foreach (range(1, 3) as $i) {
        $section = sectionCreatedBy($world['instructor'], $world, ['section_number' => '7003']);
        decideSection($world['coordinator'], $section, 'reject', 'Rejected for the test, attempt '.$i)->assertOk();
    }

    expect(Section::query()->withoutGlobalScopes()->where('section_number', '7003')->where('status', 'rejected')->count())->toBe(3);
});

test('the index itself refuses a second live section, past the form request', function (): void {
    $world = approvalWorld();
    $first = sectionCreatedBy($world['admin'], $world, ['section_number' => '7004']);

    expect(fn () => Section::factory()->create($first->only(['term_id', 'branch_id', 'course_id', 'section_number'])))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
