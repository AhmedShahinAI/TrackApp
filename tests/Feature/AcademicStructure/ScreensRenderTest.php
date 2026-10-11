<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;

/*
|--------------------------------------------------------------------------
| T196–T204 — every US4 screen renders, in both languages
|--------------------------------------------------------------------------
|
| A Blade mistake is invisible until the template is rendered. Nothing in the unit or authorization
| tests renders these nine screens, and the visual suite — which does — fails in a way that is
| easy to misread: `--update-snapshots` will cheerfully freeze a styled 500 page as the baseline
| and every later run will agree with it. That happened three times in US2 and US3 before
| refuseErrorPages() was added as a guard.
|
| So this file is the cheap, fast layer underneath that: load each screen as a signed-in admin, in
| Arabic and in English, and require 200. It catches the whole class of failure that cost those
| baselines — an undefined variable passed to a partial, a component rendered through view()
| instead of Blade::render(), a translation key that resolves to nothing, a missing `use`.
|
| Both locales, because half the screens interpolate `name_{locale}` and `campus_name_{locale}`
| into a property access. A missing English column throws only when the English page is asked for.
|
*/

/** An admin who can see and do everything, so a 403 never masquerades as a render failure. */
function aScreenAdmin(): App\Models\User
{
    return aUser('SUPER_ADMIN');
}

dataset('us4 screens', [
    'terms' => '/terms',
    'branches' => '/branches',
    'courses' => '/courses',
    'sections' => '/sections',
    'students' => '/students',
]);

test('each US4 list screen renders in Arabic', function (string $path): void {
    test()->actingAs(aScreenAdmin())->get($path)->assertOk();
})->with('us4 screens');

test('each US4 list screen renders in English', function (string $path): void {
    $admin = aScreenAdmin();
    $admin->forceFill(['locale' => 'en'])->saveQuietly();

    test()->actingAs($admin->fresh())->get($path)->assertOk();
})->with('us4 screens');

test('the section detail screen renders with a roster, a withdrawal and a history row', function (): void {
    $section = Section::factory()->create();

    $active = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'active',
    ]);

    /*
     * A withdrawn row as well as an active one. The withdrawn branch of the roster renderer is the
     * one that reads `withdraw_reason` and suppresses the per-row action, and a fixture with only
     * active rows would never execute it.
     */
    SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'withdrawn',
        'withdrawn_at' => now(),
        'withdraw_reason' => 'انسحاب نظامي',
    ]);

    $response = test()->actingAs(aScreenAdmin())->get("/sections/{$section->id}")->assertOk();

    app()->setLocale('ar');

    $response->assertSee(__('sections.tabs.roster'), false)
        ->assertSee(__('sections.tabs.history'), false)
        ->assertSee('انسحاب نظامي', false);

    expect($active->fresh()->status)->toBe('active');
});

test('the student detail screen renders, and keeps a withdrawn enrollment visible', function (): void {
    $student = Student::factory()->create();
    $section = Section::factory()->create();

    SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => $student->id,
        'status' => 'withdrawn',
        'withdrawn_at' => now(),
        'withdraw_reason' => 'انتقال إلى شعبة أخرى',
    ]);

    /*
     * Constitution V at the surface. A withdrawal that removed the row would make this page answer
     * "who is enrolled" and lose "who left, and why" — and the reason WithdrawEnrollment insists on
     * would be written and never read by anyone.
     */
    test()->actingAs(aScreenAdmin())
        ->get("/students/{$student->id}")
        ->assertOk()
        ->assertSee('انتقال إلى شعبة أخرى', false);
});

test('the sections screen offers no branch filter to an instructor', function (): void {
    $instructor = aUser('INSTRUCTOR');
    Section::factory()->create(['instructor_id' => $instructor->id]);

    $html = test()->actingAs($instructor)->get('/sections')->assertOk()->getContent();

    /*
     * Not styling. An instructor has one branch, so a branch select would be a control that cannot
     * change anything — and rendering one would also imply there is something else to see.
     *
     * Scoped to the filter form rather than the whole page: the create dialog has a `branch_id`
     * select too, and matching on the raw page would pass or fail for the wrong reason depending on
     * whether this account happens to hold `section.create`.
     */
    // before(after(...)), not Str::between: between() cuts at the LAST closing tag, which here
    // swallowed the rest of the document and made the assertion read a form it was not about.
    $filterRow = Str::before(Str::after($html, 'class="track-filters'), '</form>');

    expect($filterRow)->not->toContain('name="branch_id"')
        ->and($filterRow)->toContain('name="course_id"');
});

test('every dialog the section page dispatches at actually exists on the page', function (): void {
    $section = Section::factory()->create();

    SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'active',
    ]);

    $html = test()->actingAs(aScreenAdmin())->get("/sections/{$section->id}")->assertOk()->getContent();

    /*
     * The structural half of a bug the visual suite found the expensive way.
     *
     * The per-row withdraw button dispatches `track-dialog-open` with an id, and x-track-dialog
     * only responds when that id matches its own. The button was first written with a bare `$id`
     * in the Alpine expression, which Blade leaves untouched in an attribute — so Alpine resolved
     * it to its own `$id` magic helper, the event carried a function rather than a string, and
     * clicking did nothing. Nothing in the markup looked wrong.
     *
     * So: collect every id the page dispatches at, and require a matching element for each. This
     * catches a typo, a renamed dialog and the `$id` mistake in one assertion, in milliseconds.
     */
    preg_match_all("/track-dialog-open', \{ id: '([^']+)' \}/", (string) $html, $dispatched);

    expect($dispatched[1])->not->toBeEmpty('the page dispatches no dialog at all');

    foreach (array_unique($dispatched[1]) as $id) {
        expect($html)->toContain('id="'.$id.'"');
    }

    // And nothing resolved to Alpine's magic instead of a string.
    expect($html)->not->toContain('{ id: $id }');
});
