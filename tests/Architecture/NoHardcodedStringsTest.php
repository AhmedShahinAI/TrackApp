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
 *
 * Order matters. Comments, scripts and PHP come out first; then Blade expressions, because they can
 * contain `>` and would otherwise survive the tag pass as fragments; then tags.
 */
function viewTextNodes(string $contents): string
{
    $patterns = [
        '/\{\{--.*?--\}\}/s',           // Blade comments
        '/<!--.*?-->/s',                // HTML comments
        '/<script\b.*?<\/script>/is',   // inline JS
        '/<style\b.*?<\/style>/is',     // inline CSS
        '/@php.*?@endphp/s',            // PHP blocks
        '/\{\{.*?\}\}/s',               // echoes — already PHP, judged by the PHP guards
        '/\{!!.*?!!\}/s',               // raw echoes
        // Blade directives with arguments, at ANY nesting depth. The previous pattern allowed one
        // level of nested parentheses, so `@if ($u->is_active && ! $u->is(auth()->user()))` left a
        // fragment behind that the scanner then reported as prose — a false positive the accounts
        // screen produced immediately. (?1) recurses into the group.
        '/@[a-zA-Z]+\s*(\((?:[^()]++|(?1))*\))/s',
        '/@[a-zA-Z]+/s',                // bare Blade directives
        // Tags, matched so a quoted attribute value may contain `>`. The naive `<[^>]*>` stops at
        // the first `>` inside `:dot="$count > 0"` and leaves the rest of the tag looking like
        // prose — which is exactly how this scanner produced six false positives before.
        '/<[a-zA-Z\/!](?:"[^"]*"|\'[^\']*\'|[^>"\'])*>/s',
        // A dynamic tag name - `<{{ $tag }} href="...">`, which button and icon-button use to be
        // either a <button> or an <a>. By this point the echo has been stripped, so the tag opens
        // with `<` and whitespace and the pattern above has no tag name to anchor on.
        '/<\s(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>/s',
        '/<\/\s*>/s',
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

/**
 * @return list<string>
 */
function translatableViewFiles(): array
{
    $viewPath = resource_path('views');

    if (! is_dir($viewPath)) {
        return [];
    }

    $files = [];

    // Dev-only fixture surfaces are excluded by path. The component gallery is specimen markup
    // whose purpose is to show every component with representative content; translating a test
    // fixture would make it a worse fixture, and it is unreachable outside APP_ENV=local.
    $finder = Finder::create()->files()->in($viewPath)->notPath('dev')->name('*.blade.php');

    foreach ($finder as $file) {
        $files[] = (string) $file->getRealPath();
    }

    return $files;
}

test('no user-facing literal sits outside the translator', function (): void {
    $offenders = [];

    foreach (translatableViewFiles() as $path) {
        $text = viewTextNodes((string) file_get_contents($path));

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if (isTranslatableProse($line)) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
                $offenders[] = $relative.': '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
})->skip(
    fn (): bool => ! is_dir(resource_path('views')),
    'No views yet - Phase 3 creates the Blade layer.',
);

test('there is no allowlist', function (): void {
    // Laravel's stock welcome page was the one candidate for an exemption. It was rewritten to use
    // tokens and __() instead, because a guard with a standing exemption is a guard nobody trusts.
    // If a view genuinely cannot be translated, that is a conversation in review, not a quiet entry
    // in an array here.
    $source = (string) file_get_contents(__FILE__);

    expect(str_contains($source, '$allow'.'list'))->toBeFalse(
        'This test has gained an allowlist. Justify the exemption in review rather than in the test.',
    );
});

test('the scanner finds prose and ignores markup', function (): void {
    // The guard is only worth having if it fires. These are the cases that matter: a bare sentence
    // is caught, a translated one is not, and neither attributes, numerals, nor an attribute value
    // containing `>` are prose.
    expect(translatableViewFiles())->not->toBeEmpty();

    $caught = viewTextNodes('<p class="lead">Create a new section</p>');
    expect(isTranslatableProse($caught))->toBeTrue();

    $translated = viewTextNodes('<p class="lead">{{ __("sections.create") }}</p>');
    expect(isTranslatableProse($translated))->toBeFalse();

    $directive = viewTextNodes('<span>@lang("sections.title")</span>');
    expect(isTranslatableProse($directive))->toBeFalse();

    expect(isTranslatableProse(viewTextNodes('<td>1,204</td>')))->toBeFalse();
    expect(isTranslatableProse(viewTextNodes('<hr aria-label="divider">')))->toBeFalse();

    // The regression this scanner was fixed for: a `>` inside a quoted attribute value.
    $greaterThan = viewTextNodes('<x-track-icon-button :dot="$notificationCount > 0" icon="bell" />');
    expect(isTranslatableProse($greaterThan))->toBeFalse();

    // And a multi-line tag, which is how every component in this project is written.
    $multiline = viewTextNodes("<x-track-button\n    variant=\"primary\"\n    :disabled=\"\$count > 3\"\n/>");
    expect(isTranslatableProse($multiline))->toBeFalse();
});
