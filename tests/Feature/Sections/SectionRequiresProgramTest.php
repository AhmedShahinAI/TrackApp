<?php

declare(strict_types=1);

use App\Models\Section;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T309 — every section belongs to exactly one programme (FR-090, SC-019)
|--------------------------------------------------------------------------
*/

test('a section cannot be created without a programme', function (): void {
    $payload = aValidSectionRequest();
    unset($payload['program_id']);

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.sections.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.program_id.0', __('sections.errors.program_required'));

    expect(Section::query()->count())->toBe(0);
});

test('with one it is created, and the programme is on the record', function (): void {
    $payload = aValidSectionRequest();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.sections.store'), $payload)
        ->assertCreated();

    expect(Section::query()->sole()->program_id)->toBe($payload['program_id']);
});

test('an edit cannot set the programme to null', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create(['program_id' => aProgram()->id]);

    $this->actingAs($admin)
        ->putJson(route('api.v1.sections.update', $section), ['program_id' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['program_id']);

    expect($section->fresh()->program_id)->not->toBeNull();
});

test('the column refuses a null programme whoever writes it', function (): void {
    $section = Section::factory()->create();

    expect(fn () => DB::table('sections')->where('id', $section->id)->update(['program_id' => null]))
        ->toThrow(QueryException::class);
});

test('zero sections exist without a programme after the roster import (SC-019)', function (): void {
    $world = importWorld();

    importRoster($world, rosterRows($world, 3));
    importRoster($world, [rosterRow($world, ['section_number' => '132'])]);

    expect(Section::query()->withoutGlobalScopes()->count())->toBe(2)
        ->and(Section::query()->withoutGlobalScopes()->whereNull('program_id')->count())->toBe(0)
        ->and(Section::query()->withoutGlobalScopes()->pluck('program_id')->unique()->all())->toBe([$world['program']->id]);
});
