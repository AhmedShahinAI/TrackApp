<?php

declare(strict_types=1);

require_once __DIR__.'/SupportScreens.php';

/*
|--------------------------------------------------------------------------
| T444 + T449 — no raw key, no other language's text, and the platform's digits and dates
|--------------------------------------------------------------------------
|
| A raw key ("support.staff.fields.branch") is a missing translation that renders anyway; a fallback
| string is the English one standing in for a missing Arabic one, which reads fine to a developer and
| is wrong for every Arabic reader. Each is checked against the screens as people reach them.
|
*/

/**
 * The English strings of the support namespace long enough to be recognisable, placeholders removed —
 * any of them on an Arabic page is the fallback showing through.
 *
 * @return list<string>
 */
function englishSupportStrings(): array
{
    $strings = [];

    /** @var array<string, mixed> $english */
    $english = require lang_path('en/support.php');

    array_walk_recursive($english, function (mixed $value) use (&$strings): void {
        if (! is_string($value) || str_contains($value, '|')) {
            return;
        }

        foreach (preg_split('/:[a-z_]+/', $value) ?: [] as $fragment) {
            $fragment = trim($fragment, ' .,:;()—-');

            if (mb_strlen($fragment) >= 12) {
                $strings[] = $fragment;
            }
        }
    });

    return array_values(array_unique($strings));
}

test('no support screen prints a raw translation key', function (string $locale): void {
    foreach (supportScreensIn($locale) as $screen => $html) {
        expect(readableText($html))->not->toMatch('/\b(support|validation|common|nav|auth)\.[a-z_]+\.[a-z_.]+/', "$screen in $locale");
    }
})->with(['ar', 'en']);

test('no English screen carries Arabic text', function (): void {
    foreach (supportScreensIn('en') as $screen => $html) {
        // «عربي» is the language switch's own name for Arabic — each language named in itself, by design.
        $text = str_replace('عربي', '', readableText($html));

        expect(preg_match('/\p{Arabic}/u', $text))->toBe(0, "$screen: ".mb_substr((string) preg_replace('/[^\p{Arabic}\s]/u', '', $text), 0, 80));
    }
});

test('no Arabic screen falls back to the English string', function (): void {
    $english = englishSupportStrings();

    expect($english)->not->toBeEmpty();

    foreach (supportScreensIn('ar') as $screen => $html) {
        $text = readableText($html);

        foreach ($english as $string) {
            expect(str_contains($text, $string))->toBeFalse("$screen shows the English «{$string}»");
        }
    }
});

test('digits are 0–9 and dates read DD/MM/YYYY in both languages (T449)', function (string $locale): void {
    $screens = supportScreensIn($locale);

    foreach ($screens as $screen => $html) {
        expect(preg_match('/[٠-٩۰-۹]/u', readableText($html)))->toBe(0, "$screen in $locale has Arabic-Indic digits");
    }

    // The two screens that print a date print it the platform's way.
    expect(readableText($screens['status-result']))->toMatch('#\b\d{2}/\d{2}/\d{4}\b#')
        ->and(readableText($screens['detail']))->toMatch('#\b\d{2}/\d{2}/\d{4} \d{2}:\d{2}\b#');
})->with(['ar', 'en']);

test('the waiting time is the platform\'s words, not Carbon\'s', function (string $locale, int $seconds, string $expected): void {
    app()->setLocale($locale);

    expect(App\Modules\Support\Http\SupportRequestPresenter::duration($seconds))->toBe($expected);
})->with([
    ['ar', 30, 'أقل من دقيقة'],
    ['ar', 90 * 60, 'ساعة و30 دقيقة'],
    ['ar', 26 * 3600, 'يوم وساعتان'],
    ['ar', 12 * 86400 + 3 * 3600, '12 يومًا و3 ساعات'],
    ['en', 90 * 60, '1 hour, 30 minutes'],
    ['en', 3 * 86400, '3 days'],
]);

test('a list of branches is joined in the reader\'s language', function (): void {
    app()->setLocale('en');
    expect(__('support.staff.list_separator'))->toBe(', ');

    app()->setLocale('ar');
    expect(__('support.staff.list_separator'))->toBe('، ');
});

test('the queue becomes stacked cards below 768px (T450)', function (): void {
    $queue = supportScreensIn('en')['queue'];

    // x-track-data-table's stacked mode: every cell names its column for the phone layout.
    expect($queue)->toContain('track-table--stack')
        ->and($queue)->toContain('data-label="'.__('support.staff.fields.status').'"');
});
