<?php

declare(strict_types=1);

use App\Models\Section;
use App\Modules\Sections\Actions\CreateSection;
use App\Modules\Sections\Actions\DecideSectionApproval;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Phase 27 code review — one test per finding, so none comes back
|--------------------------------------------------------------------------
*/

test('1 · a creator who does not teach a section loses it once it is approved', function (): void {
    $world = approvalWorld();
    $colleague = aUser('INSTRUCTOR');

    // Created for a colleague: the creator is not its instructor.
    $section = sectionCreatedBy($world['instructor'], $world, ['instructor_id' => $colleague->id]);

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->getJson('/api/v1/sections/'.$section->id)->assertOk();

    decideSection($world['coordinator'], $section, 'approve')->assertOk();

    app('auth')->forgetGuards();
    $world['instructor']->forgetScopeCache();
    $this->actingAs($world['instructor'])->getJson('/api/v1/sections/'.$section->id)->assertNotFound();
});

test('2 · an instructor who leaves instructor_id empty can still attach the roster', function (): void {
    $world = approvalWorld();

    app('auth')->forgetGuards();
    $response = $this->actingAs($world['instructor'])->post('/api/v1/sections', sectionPayload($world, [
        'roster' => rosterFile(sectionRows(2), 'list.xlsx', SECTION_HEADERS),
    ]), ['Accept' => 'application/json'])->assertCreated();

    expect($response->json('data.import_job_id'))->not->toBeNull();
});

test('2 · a roster from a creator without the import permission is refused before anything exists', function (): void {
    $world = approvalWorld();
    $before = Section::query()->withoutGlobalScopes()->count();

    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'])->post('/api/v1/sections', sectionPayload($world, [
        'roster' => rosterFile(sectionRows(2), 'list.xlsx', SECTION_HEADERS),
    ]), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('roster');

    expect(Section::query()->withoutGlobalScopes()->count())->toBe($before);
});

test('3 · the action itself refuses a return or a rejection without a real reason', function (?string $reason): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    expect(fn () => app(DecideSectionApproval::class)->execute($section, $world['coordinator'], 'reject', $reason))
        ->toThrow(ValidationException::class);

    expect($section->refresh()->status)->toBe('pending');
})->with(['none' => [null], 'blank' => ['   '], 'short' => ['too short']]);

test('4 · a section whose creator was deleted is listed and decidable alike', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    $world['instructor']->delete();

    app('auth')->forgetGuards();
    $listed = collect((array) $this->actingAs($world['coordinator'])->getJson('/api/v1/sections/approvals')->json('data'))->pluck('id')->all();

    expect($listed)->toContain($section->id);
    decideSection($world['coordinator'], $section, 'approve')->assertOk();
});

test('5 · a rejected section is out of the home dashboard count', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $section, 'reject', 'This section duplicates another one.')->assertOk();

    app('auth')->forgetGuards();
    $stats = $this->actingAs($world['admin'], 'web')->get(route('home'))->assertOk()->viewData('stats');

    expect($stats['sections'])->toBe(Section::query()->withoutGlobalScopes()->where('status', '!=', 'rejected')->count())
        ->and($stats['sections'])->toBeLessThan(Section::query()->withoutGlobalScopes()->count());
});

test('5 · the students list offers only approved sections in its filters', function (): void {
    $world = approvalWorld();
    $pending = sectionCreatedBy($world['instructor'], $world, ['section_number' => '9301']);

    app('auth')->forgetGuards();
    $html = (string) $this->actingAs($world['coordinator'], 'web')->get(route('students.index'))->assertOk()->getContent();

    // Inside the section select only: ids are shared with the branch and programme selects.
    preg_match('#<select[^>]*name="section_id".*?</select>#s', $html, $select);

    expect($select[0] ?? '')->not->toContain('value="'.$pending->id.'"')
        ->and($html)->not->toContain('· 9301');
});

test('6 · a refused roster leaves no stored file behind', function (): void {
    Storage::fake('private');
    $world = approvalWorld();
    $bad = Illuminate\Http\UploadedFile::fake()->createWithContent('roster.xlsx', 'not a spreadsheet');

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->post('/api/v1/sections', sectionPayload($world, ['roster' => $bad]), ['Accept' => 'application/json'])
        ->assertUnprocessable();

    expect(Storage::disk('private')->allFiles())->toBe([]);
});

/**
 * @param  array<string, mixed>  $world
 */
function returnedForCorrection(array $world): Section
{
    $section = sectionCreatedBy($world['instructor'], $world, ['section_number' => '9302']);
    decideSection($world['coordinator'], $section, 'return', 'Use number 9303, the timetable one.')->assertOk();
    app('auth')->forgetGuards();
    $world['instructor']->forgetScopeCache();

    return $section;
}

test('7 · the creator of a returned section gets a prefilled correction dialog', function (): void {
    $world = approvalWorld();
    $section = returnedForCorrection($world);

    $this->actingAs($world['instructor'], 'web')->get(route('sections.show', $section->id))->assertOk()
        ->assertSee('id="section-resubmit"', false)
        ->assertSee('name="section_number"', false)
        ->assertSee('value="9302"', false);
});

test('7 · nobody but the creator gets the correction dialog', function (): void {
    $world = approvalWorld();
    $section = returnedForCorrection($world);

    $this->actingAs($world['coordinator'], 'web')->get(route('sections.show', $section->id))->assertOk()
        ->assertDontSee('id="section-resubmit"', false);
});

test('8 · a number taken between the form check and the insert is a 422, not a 500', function (): void {
    $world = approvalWorld();
    $taken = sectionCreatedBy($world['admin'], $world, ['section_number' => '9304']);

    // Straight to the action: the form request's own uniqueness check is what a race slips past.
    expect(fn () => app(CreateSection::class)->execute($world['instructor'], $taken->only(['term_id', 'branch_id', 'course_id', 'program_id', 'section_number'])))
        ->toThrow(ValidationException::class);
});

test('9 · the targeting guard catches raw SQL and a concatenated table name', function (): void {
    $catches = fn (string $code): bool => preg_match('/\binitiative_(sections|targets)\b/', $code) === 1
        || preg_match('/[\'"]initiative_[\'"]\s*\./', $code) === 1;

    expect($catches('DB::select("select section_id from initiative_targets where id = ?")'))->toBeTrue()
        ->and($catches("DB::table('initiative_'.'sections')"))->toBeTrue()
        ->and($catches('$table = "initiative_sections";'))->toBeTrue()
        ->and($catches('TargetableSection::query()'))->toBeFalse();
});

test('10 · an array status is no match, not a 500', function (): void {
    $world = approvalWorld();
    sectionCreatedBy($world['admin'], $world);

    app('auth')->forgetGuards();
    $this->actingAs($world['admin'])->getJson('/api/v1/sections?status[]=active')->assertOk()->assertJsonCount(0, 'data');
});
