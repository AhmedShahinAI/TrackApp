<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T054 — the two languages hold the same keys
|--------------------------------------------------------------------------
|
| Constitution VI. Arabic and English are both first-class, which means a key added to one has to be
| added to the other. A missing key does not raise an error in Laravel — it renders the key itself,
| so `sections.create_button` appears on screen to a real user. That failure is invisible to anyone
| testing in the other language, which is exactly why it is a build gate rather than a review item.
|
| The translation files land in Phase 3 with the layout shell. Until then the comparison has nothing
| to compare, and the test says so rather than passing quietly. One directory existing without the
| other is a real failure and is reported as one.
|
*/

/**
 * Flatten a locale directory into dotted keys: `sections.php` returning `['title' => 'x']` becomes
 * `sections.title`.
 *
 * @return list<string>
 */
function localizationKeys(string $locale): array
{
    $directory = lang_path($locale);

    if (! is_dir($directory)) {
        return [];
    }

    $keys = [];

    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
        $group = $file->getBasename('.php');

        /** @var array<string, mixed> $translations */
        $translations = require $file->getRealPath();

        foreach (flattenTranslationKeys($translations, $group) as $key) {
            $keys[] = $key;
        }
    }

    // JSON translation files are flat and carry no group prefix.
    $json = lang_path($locale.'.json');

    if (is_file($json)) {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);

        foreach (array_keys($decoded) as $key) {
            $keys[] = 'json:'.$key;
        }
    }

    sort($keys);

    return array_values(array_unique($keys));
}

/**
 * @param  array<array-key, mixed>  $translations
 * @return list<string>
 */
function flattenTranslationKeys(array $translations, string $prefix): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $path = $prefix.'.'.$key;

        if (is_array($value)) {
            foreach (flattenTranslationKeys($value, $path) as $nested) {
                $keys[] = $nested;
            }

            continue;
        }

        $keys[] = $path;
    }

    return $keys;
}

function anyLocaleFilesExist(): bool
{
    return is_dir(lang_path('ar')) || is_file(lang_path('ar.json'))
        || is_dir(lang_path('en')) || is_file(lang_path('en.json'));
}

test('ar and en hold identical key sets', function (): void {
    $arabicExists = is_dir(lang_path('ar')) || is_file(lang_path('ar.json'));
    $englishExists = is_dir(lang_path('en')) || is_file(lang_path('en.json'));

    expect($arabicExists)->toBeTrue('lang/en exists but lang/ar does not.');
    expect($englishExists)->toBeTrue('lang/ar exists but lang/en does not.');

    $arabic = localizationKeys('ar');
    $english = localizationKeys('en');

    expect(array_values(array_diff($arabic, $english)))->toBe([], 'Keys present in ar but missing from en.');
    expect(array_values(array_diff($english, $arabic)))->toBe([], 'Keys present in en but missing from ar.');
})->skip(
    // Phase 3 (T057 onward) creates these. Skipping is honest while neither exists; passing would
    // claim a guarantee about files that are not there. One without the other is a real failure and
    // is reported as one by the assertions above.
    fn (): bool => ! anyLocaleFilesExist(),
    'No translation files yet - Phase 3 creates lang/ar and lang/en.',
);

test('the flattener produces the dotted keys the comparison relies on', function (): void {
    // Proves the comparison would catch a real divergence rather than comparing two empty lists.
    $keys = flattenTranslationKeys([
        'title' => 'الشعب',
        'actions' => ['create' => 'إضافة', 'nested' => ['deep' => 'أعمق']],
    ], 'sections');

    expect($keys)->toBe([
        'sections.title',
        'sections.actions.create',
        'sections.actions.nested.deep',
    ]);

    expect(array_values(array_diff($keys, ['sections.title'])))->toBe([
        'sections.actions.create',
        'sections.actions.nested.deep',
    ]);
});
