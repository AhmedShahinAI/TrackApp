<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T055 — every user-facing string in a view goes through the translator
|--------------------------------------------------------------------------
|
| Constitution VI, FR-062. A literal in a Blade template is a string that exists in one language
| only, and nothing will ever report it: the Arabic screen renders English, or the English screen
| renders Arabic, and both look deliberate.
|
| The scan is on text nodes, not on the whole file. Attribute values, class names, Alpine
| expressions, and `{{ }}` echoes are not user-facing prose and are left alone — a guard that
| flagged `class="btn"` would be turned off within a week.
|
*/

/**
 * Strip everything from a Blade template that is not a bare text node.
 */
function viewTextNodes(string $contents): string
{
    $patterns = [
        '/\{\{--.*?--\}\}/s',          // Blade comments
        '/<!--.*?-->/s',               // HTML comments
        '/<script\b.*?<\/script>/is',  // inline JS
        '/<style\b.*?<\/style>/is',    // inline CSS
        '/@php.*?@endphp/s',           // PHP blocks
        '/\{\{.*?\}\}/s',              // echoes — already PHP, judged by the PHP guards
        '/\{!!.*?!!\}/s',              // raw echoes
        '/<[^>]*>/s',                  // all tags, which takes every attribute with them
        '/@[a-zA-Z]+(\s*\([^)]*\))?/s', // Blade directives and their arguments
    ];

    return (string) preg_replace($patterns, ' ', $contents);
}

/**
 * Words that are not prose in any language: numerals, punctuation, entities, and single letters.
 */
function isTranslatableProse(string $text): bool
{
    $trimmed = trim($text);

    if ($trimmed === '') {
        return false;
    }

    // Numerals, punctuation, separators, HTML entities, and units are the same in both languages.
    if (preg_match('/^[\s\d\p{P}\p{S}]+$/u', $trimmed) === 1) {
        return false;
    }

    // A single character is a bullet, a dash, or an icon glyph, not a sentence.
    return mb_strlen($trimmed) > 1;
}

test('no user-facing literal sits outside the translator', function (): void {
    // welcome.blade.php is Laravel's stock landing page. Phase 3 replaces it with the application
    // shell (T057 onward); this entry must be deleted when that happens, and the test below asserts
    // the allowlist stays honest.
    $allowlist = ['welcome.blade.php'];

    $viewPath = resource_path('views');
    $offenders = [];

    foreach (Finder::create()->files()->in($viewPath)->name('*.blade.php') as $file) {
        if (in_array($file->getFilename(), $allowlist, true)) {
            continue;
        }

        $text = viewTextNodes((string) file_get_contents((string) $file->getRealPath()));

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if (isTranslatableProse($line)) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', (string) $file->getRealPath());
                $offenders[] = $relative.': '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
})->skip(
    fn (): bool => ! is_dir(resource_path('views')),
    'No views yet - Phase 3 creates the Blade layer.',
);

test('every allowlisted view still exists', function (): void {
    // An allowlist that outlives the file it excuses is how a guard quietly stops guarding.
    foreach (['welcome.blade.php'] as $filename) {
        expect(is_file(resource_path('views/'.$filename)))->toBeTrue(
            "views/{$filename} is allowlisted in NoHardcodedStringsTest but no longer exists — remove the entry.",
        );
    }
});

test('the scanner finds prose and ignores markup', function (): void {
    // The guard is only worth having if it fires. These four cases are the ones that matter: a bare
    // sentence is caught, a translated one is not, and neither attributes nor numerals are prose.
    $caught = viewTextNodes('<p class="lead">Create a new section</p>');
    expect(isTranslatableProse($caught))->toBeTrue();

    $translated = viewTextNodes('<p class="lead">{{ __("sections.create") }}</p>');
    expect(isTranslatableProse($translated))->toBeFalse();

    $directive = viewTextNodes('<span>@lang("sections.title")</span>');
    expect(isTranslatableProse($directive))->toBeFalse();

    expect(isTranslatableProse(viewTextNodes('<td>1,204</td>')))->toBeFalse();
    expect(isTranslatableProse(viewTextNodes('<hr aria-label="divider">')))->toBeFalse();
});
