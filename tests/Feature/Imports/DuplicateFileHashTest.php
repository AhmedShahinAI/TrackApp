<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\AcademicTerm;

/*
|--------------------------------------------------------------------------
| T214 — the same file for the same term warns, and continuing creates nothing (FR-067, SC-008)
|--------------------------------------------------------------------------
|
| "The same file" is the same bytes, so the second upload is a byte-for-byte copy of the first —
| two writes of one roster are not one file, because xlsx records its own creation time.
|
| The roster includes rows the first import skipped (an unknown course, "skip" chosen). The second
| import chooses "create" for them. A natural re-run would therefore create that course and its
| students; FR-067 says proceeding creates zero, so the second run must not — which is the case that
| separates "nothing new happened to be needed" from "a re-import creates nothing".
|
*/

test('a re-upload of the same file for the same term is flagged and creates zero records', function (): void {
    $world = importWorld();
    $file = rosterFile([
        ...rosterRows($world, 4),
        rosterRow($world, ['course_code' => 'PHYS110', 'section_number' => '401']),
    ]);
    $again = sameFileAgain($file);

    $first = importRoster($world, [], [], $file);
    expect($first->status)->toBe('completed')->and($first->imported)->toBe(4);

    $before = CoreCounts::snapshot();

    $response = $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => $again, 'term_id' => $world['term']->id,
    ], ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonPath('data.duplicate_of', $first->id);

    $second = App\Models\ImportJob::query()->findOrFail($response->json('data.id'));
    $second = previewAndCommit($world['admin'], mapAndValidate($world['admin'], $second, [
        'create_missing_courses' => true,
        'new_course_program_id' => $world['program']->id,
    ]));

    expect($second->status)->toBe('completed')
        ->and($second->imported)->toBe(0)
        ->and(CoreCounts::snapshot())->toBe($before);
});

test('the warning is shown on the wizard before anything else happens', function (): void {
    $world = importWorld();
    $file = rosterFile(rosterRows($world, 2));
    $again = sameFileAgain($file);

    importRoster($world, [], [], $file);
    $second = uploadRoster($world['admin'], $again, $world['term']->id);

    $this->actingAs($world['admin'])->get(route('imports.show', $second))
        ->assertOk()
        ->assertSee(__('imports.upload.previously_imported'));
});

test('the same file for a different term is not a duplicate', function (): void {
    $world = importWorld();
    $file = rosterFile(rosterRows($world, 2));
    $again = sameFileAgain($file);

    $first = importRoster($world, [], [], $file);
    $other = AcademicTerm::factory()->create(['status' => 'draft']);

    $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => $again, 'term_id' => $other->id,
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.duplicate_of', null);

    expect($first->duplicateOf())->toBeNull();
});

test('an abandoned earlier upload of the same file is not "previously imported"', function (): void {
    $world = importWorld();
    $file = rosterFile(rosterRows($world, 2));
    $again = sameFileAgain($file);

    // Uploaded and mapped, never committed: nothing was imported, so there is nothing to warn about.
    mapAndValidate($world['admin'], uploadRoster($world['admin'], $file, $world['term']->id));

    $this->actingAs($world['admin'])->post(route('api.v1.imports.store'), [
        'file' => $again, 'term_id' => $world['term']->id,
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.duplicate_of', null);
});
