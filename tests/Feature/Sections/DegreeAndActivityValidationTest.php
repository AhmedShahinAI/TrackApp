<?php

declare(strict_types=1);

use App\Models\Section;
use App\Modules\Settings\Settings;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T310 — degree and activity come from settings, not from the schema (FR-108, FR-109)
|--------------------------------------------------------------------------
*/

/**
 * What a Super Admin's edit on the settings screen does: replace the stored list.
 *
 * @param  list<array{value: string, ar: string, en: string}>  $rows
 */
function replaceSettingList(string $key, array $rows): void
{
    DB::table('settings')->where('key', $key)->update(['value' => json_encode($rows)]);
    Settings::forget();
}

test('a value outside the configured set is refused, for both fields', function (string $field, string $value): void {
    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.sections.store'), aValidSectionRequest([$field => $value]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.'.$field.'.0', __('validation.in_setting', ['attribute' => __('validation.attributes.'.$field)]));

    expect(Section::query()->count())->toBe(0);
})->with([
    'degree' => ['degree', 'doctorate'],
    'activity' => ['activity', 'seminar'],
    'case differs' => ['degree', 'Bachelor'],
]);

test('adding a value to the setting makes it valid at once — no migration, no deployment', function (string $field, string $key, string $value): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)
        ->postJson(route('api.v1.sections.store'), aValidSectionRequest([$field => $value]))
        ->assertUnprocessable();

    replaceSettingList($key, [...Settings::options($key), ['value' => $value, 'ar' => 'قيمة جديدة', 'en' => 'New value']]);

    $this->actingAs($admin)
        ->postJson(route('api.v1.sections.store'), aValidSectionRequest([$field => $value]))
        ->assertCreated();

    expect(Section::query()->sole()->{$field})->toBe($value);
})->with([
    'degree' => ['degree', Settings::SECTION_DEGREES, 'master'],
    'activity' => ['activity', Settings::SECTION_ACTIVITIES, 'lab'],
]);

test('the section dialog offers exactly the configured values, in the reader\'s language', function (): void {
    $admin = aUser('SUPER_ADMIN'); // first: it seeds the settings this test then edits

    replaceSettingList(Settings::SECTION_ACTIVITIES, [
        ['value' => 'lecture', 'ar' => 'محاضرة', 'en' => 'Lecture'],
        ['value' => 'lab', 'ar' => 'عملي', 'en' => 'Lab'],
    ]);

    $this->actingAs($admin)->get(route('sections.index'))
        ->assertOk()
        ->assertSee('<option value="lab"', false)
        ->assertSee('عملي');
});
