<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

/*
|--------------------------------------------------------------------------
| T260 — no screen shows a raw translation key, or the other language's words (FR-075)
|--------------------------------------------------------------------------
|
| LocalizationParityTest proves both lang trees have the same keys. It cannot see a view that asks
| for a key nobody wrote — `__('imports.reasons.typo')` prints "imports.reasons.typo" — or one that
| prints a sentence in the wrong language because it was hard-coded or fell back. Those only show on
| a rendered page, so this renders every screen in each language and reads it.
|
| "The other language's words" is measured with the other locale's own strings: an English string
| from lang/en that appears on an Arabic page got there by mistake. Any string with a real word in it
| counts — a two-word label like "Term name" leaked through the earlier 14-character floor — and a
| `:placeholder` matches whatever was put in its place. Strings with no word of three letters ("EN",
| "xlsx" is one but is the same in both files) and strings identical in both files are not evidence.
|
*/

/**
 * Every translation as "file.key" => value, for one locale.
 *
 * @return array<string, string>
 */
function translations(string $locale): array
{
    $all = [];

    foreach (glob(lang_path($locale.'/*.php')) ?: [] as $file) {
        $group = basename($file, '.php');

        foreach (Arr::dot(require $file) as $key => $value) {
            if (is_string($value)) {
                $all[$group.'.'.$key] = $value;
            }
        }
    }

    return $all;
}

/**
 * The strings that exist only in one language — different from the other locale's string for the
 * same key, with a real word in them — split by how they can be told from data.
 *
 * A phrase ("Term name", "Unknown course: :code") is matched anywhere in the page, a `:placeholder`
 * matching whatever filled it. A single word ("Language", "صالح") is matched only as a whole label —
 * a text node or a spoken attribute on its own — because one word inside a cell is as likely to be a
 * course called "English Language" or a student called صالح as it is a leak.
 *
 * @return array{phrases: array<string, string>, words: list<string>}
 */
function sentencesOnlyIn(string $locale, string $other): array
{
    $theirs = translations($other);
    $phrases = [];
    $words = [];

    foreach (translations($locale) as $key => $value) {
        $literal = trim((string) preg_replace('/:[a-z_]+/i', '', $value));

        if (($theirs[$key] ?? null) === $value || preg_match('/\p{L}{3,}/u', $literal) !== 1) {
            continue;
        }

        if (preg_match('/^\p{L}+$/u', trim($value)) === 1) {
            $words[] = trim($value);

            continue;
        }

        $parts = preg_split('/:[a-z_]+/i', $value) ?: [];
        $body = implode('.+?', array_map(static fn (string $part): string => preg_quote($part, '/'), $parts));

        // Word-bounded, so "Name" does not match inside "Username".
        $phrases['/(?<![\p{L}\p{N}])'.$body.'(?![\p{L}\p{N}])/u'] = $value;
    }

    return ['phrases' => $phrases, 'words' => array_values(array_unique($words))];
}

/**
 * Each text node and spoken attribute of a page, trimmed — the labels a reader meets one by one.
 *
 * @return list<string>
 */
function readableLabels(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $labels = [];

    foreach ($xpath->query('//body//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::template)] | //@aria-label | //@title | //@placeholder | //@alt') ?: [] as $node) {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $node->nodeValue));

        if ($text !== '') {
            $labels[] = $text;
        }
    }

    return $labels;
}

test('no screen prints a translation key or the other language\'s sentences', function (string $key): void {
    seedScreens();

    $keys = array_keys(translations('en'));

    // Any "group.key" for a lang file, written or not: `__('audit.'.$typo)` prints a key that exists
    // in no file, which a list of the keys that do exist cannot find.
    $groups = implode('|', array_map(static fn (string $f): string => basename($f, '.php'), glob(lang_path('en/*.php')) ?: []));

    foreach (['ar' => 'en', 'en' => 'ar'] as $locale => $other) {
        $html = (string) renderScreen($key, $locale)->assertOk()->getContent();
        $text = readableText($html);

        $rawKeys = array_values(array_filter($keys, static fn (string $k): bool => str_contains($text, $k)));
        // Any letter after the dot: a key built from a translated label reads `audit.الفروع`.
        preg_match_all('/(?<![\p{L}\p{N}_.@\/-])(?:'.$groups.')\.\p{L}[\p{L}\p{N}_]*(?:\.[\p{L}\p{N}_]+)*(?![\p{L}\p{N}_\/-])/u', $text, $unwritten);
        $rawKeys = array_values(array_unique([...$rawKeys, ...$unwritten[0]]));
        expect($rawKeys)->toBe([], "{$key} · {$locale}: raw translation keys");

        $other_ = sentencesOnlyIn($other, $locale);
        $labels = readableLabels($html);
        $leaked = array_values(array_intersect($other_['words'], $labels));

        foreach ($other_['phrases'] as $pattern => $sentence) {
            if (preg_match($pattern, $text) === 1) {
                $leaked[] = $sentence;
            }
        }

        expect($leaked)->toBe([], "{$key} · {$locale}: {$other} text on a {$locale} page");
    }
})->with(array_keys(screens()));
