<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/*
|--------------------------------------------------------------------------
| x-track-select submits a keyed map's key, and a list's own value
|--------------------------------------------------------------------------
|
| The component chose with `is_int($key)`, so every id-keyed map — terms, branches, courses,
| programmes, instructors, all keyed by integer id — rendered `<option value="the label">`. Each of
| those forms then failed validation when a browser posted it, which no API-level test could see.
| Found by the Phase 15 confirmation panel's own test.
|
*/

/** @param  array<int|string, string|array<string, string>>  $options */
function renderSelect(array $options): string
{
    return Blade::render('<x-track-select name="pick" :options="$options" />', ['options' => $options]);
}

test('an id-keyed map submits the id', function (): void {
    $html = renderSelect([7 => 'CS102 · برمجة 2', 12 => 'IT102 · برمجة 2']);

    expect($html)->toContain('<option value="7"')
        ->and($html)->toContain('<option value="12"')
        ->and($html)->not->toContain('value="CS102');
});

test('a string-keyed map submits the key', function (): void {
    expect(renderSelect(['male' => 'طلاب', 'female' => 'طالبات']))->toContain('<option value="female"');
});

test('a plain list submits its own value — the mapping step\'s header names', function (): void {
    expect(renderSelect(['رقم الطالب', 'اسم الطالب']))->toContain('<option value="اسم الطالب"');
});

test('rows of value and label are read as such', function (): void {
    expect(renderSelect([['value' => 'bachelor', 'label' => 'البكالوريوس']]))->toContain('<option value="bachelor"');
});

test('a map with a leading "all" option still submits keys — the audit actor filter\'s shape', function (): void {
    $html = renderSelect(['' => 'الكل', 3 => 'أحمد', 9 => 'سارة']);

    expect($html)->toContain('<option value=""')->toContain('<option value="9"');
});

test('a map keyed exactly 0..n-1 is read as a list — which is why such maps must be written as rows', function (): void {
    // The one shape inference cannot tell apart (see the component's note). Pinned so a change to
    // the rule is a deliberate one, and so the remedy is shown beside it.
    expect(renderSelect(['0' => 'موقوف', '1' => 'فعّال']))->toContain('<option value="موقوف"')
        ->and(renderSelect([['value' => '0', 'label' => 'موقوف'], ['value' => '1', 'label' => 'فعّال']]))
        ->toContain('<option value="0"')->toContain('<option value="1"');
});

test('a submitted value is preselected again, whether it arrives as an int or a string', function (): void {
    $html = Blade::render('<x-track-select name="pick" :options="$options" :selected="$selected" />', [
        'options' => [7 => 'سبعة', 12 => 'اثنا عشر'],
        'selected' => '12',
    ]);

    expect($html)->toMatch('/<option value="12"\s+selected/');
});

test('the section dialog\'s term select now posts the term id', function (): void {
    $term = App\Models\AcademicTerm::factory()->create(['status' => 'active']);

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('sections.index'))
        ->assertOk()
        ->assertSee('<option value="'.$term->id.'"', false);
});
