<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T064 — the production token layer may not drift from the design system
|--------------------------------------------------------------------------
|
| design-system.md §2: the design system's custom properties are the canonical token layer and are
| copied verbatim, subject only to the §6 accessibility corrections. "Verbatim" is a promise that
| needs enforcing, because the failure mode is silent — a value tweaked during a screen task looks
| fine on that screen and quietly puts the production palette out of step with the approved one.
|
| The seven exceptions are read from ACCESSIBILITY-DELTA.md rather than hard-coded here, so the
| record the design author reviews is the same record the build enforces. A correction nobody wrote
| down fails this test; a table entry that does not match the CSS fails it too.
|
| The ratios are recomputed from the hex values, so a wrong figure in the delta document is a build
| failure rather than a document nobody checks.
|
*/

/**
 * Parse `--name: value` pairs out of a CSS file, ignoring comments.
 *
 * @return array<string, string>
 */
function tokenMap(string $path): array
{
    $css = (string) file_get_contents($path);

    // Strip comments first. Several tokens carry a trailing `/* @kind color */` annotation, and the
    // §6 corrections carry `/* A11Y-0n */`, neither of which is part of the value.
    $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

    preg_match_all('/(--[a-z0-9-]+)\s*:\s*([^;}]+)/i', $css, $matches, PREG_SET_ORDER);

    $tokens = [];

    foreach ($matches as $match) {
        $tokens[$match[1]] = trim((string) preg_replace('/\s+/', ' ', $match[2]));
    }

    return $tokens;
}

/**
 * The corrections the delta document authorises, keyed by the property they change.
 *
 * @return array<string, array{id: string, new: bool, was: string, becomes: string, before: float, required: float, after: float}>
 */
function authorisedCorrections(): array
{
    $path = base_path('specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md');

    expect(is_file($path))->toBeTrue('ACCESSIBILITY-DELTA.md is missing — T061 requires it.');

    $rows = [];

    foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $line) {
        // | A11Y-01 | usage | `--token` ... | `#WAS` | `#BECOMES` | 2.54:1 | 3.0:1 | 3.77:1 | crit |
        if (preg_match('/^\|\s*(A11Y-\d+)\s*\|/', $line) !== 1) {
            continue;
        }

        $cells = array_map('trim', explode('|', trim($line, "| \t")));

        if (count($cells) < 9) {
            continue;
        }

        // The cell reads "`--token` *(new)*". Keep the bare name — callers add the `--` — and note
        // whether this correction introduces the property or changes an existing one.
        $token = ltrim((string) preg_replace('/[^a-z0-9-]/i', '', explode('*', $cells[2])[0]), '-');

        $rows[$token] = [
            'id' => $cells[0],
            'new' => str_contains($cells[2], '(new)'),
            'was' => mb_strtoupper(trim($cells[3], '`# ')),
            'becomes' => mb_strtoupper(trim($cells[4], '`# ')),
            'before' => (float) $cells[5],
            'required' => (float) $cells[6],
            'after' => (float) $cells[7],
        ];
    }

    return $rows;
}

function relativeLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');

    $channel = static function (int $value): float {
        $c = $value / 255;

        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
        + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
        + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));
}

function contrastRatio(string $a, string $b): float
{
    $la = relativeLuminance($a);
    $lb = relativeLuminance($b);

    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

test('every design-system token exists in the production layer', function (): void {
    $missing = [];

    foreach (Finder::create()->files()->in(base_path('design-system/tokens'))->name('*.css') as $file) {
        $production = resource_path('css/tokens/'.$file->getFilename());

        // fonts.css is rewritten rather than copied — the Google Fonts @import becomes @font-face
        // blocks (T058). It declares no custom properties, so there is nothing to compare.
        if ($file->getFilename() === 'fonts.css') {
            continue;
        }

        expect(is_file($production))->toBeTrue("resources/css/tokens/{$file->getFilename()} is missing.");

        foreach (tokenMap((string) $file->getRealPath()) as $name => $value) {
            if (! array_key_exists($name, tokenMap($production))) {
                $missing[] = $file->getFilename().' '.$name;
            }
        }
    }

    expect($missing)->toBe([], 'Tokens present in the design system but absent from production.');
});

test('every value matches, except the corrections the delta document authorises', function (): void {
    $corrections = authorisedCorrections();
    $unauthorised = [];
    $applied = [];

    foreach (Finder::create()->files()->in(base_path('design-system/tokens'))->name('*.css') as $file) {
        if ($file->getFilename() === 'fonts.css') {
            continue;
        }

        $approved = tokenMap((string) $file->getRealPath());
        $production = tokenMap(resource_path('css/tokens/'.$file->getFilename()));

        foreach ($approved as $name => $value) {
            $actual = $production[$name] ?? null;

            if ($actual === $value) {
                continue;
            }

            if (! array_key_exists(ltrim($name, '-'), $corrections)) {
                $unauthorised[] = sprintf('%s: design-system "%s", production "%s"', $name, $value, $actual ?? 'ABSENT');

                continue;
            }

            $applied[] = ltrim($name, '-');
        }
    }

    expect($unauthorised)->toBe([], 'Token values changed without an entry in ACCESSIBILITY-DELTA.md.');

    // The other direction: an authorised correction that was never applied. A delta document
    // describing a change nobody made is worse than no document.
    //
    // Only the corrections that CHANGE an existing property can show up as a value difference. The
    // ones marked *(new)* add a property the design system never had, so their absence here is
    // expected — the next test checks that each of those properties exists.
    $changesExisting = array_keys(array_filter(
        $corrections,
        static fn (array $row): bool => $row['new'] === false,
    ));

    $notApplied = array_values(array_diff($changesExisting, $applied));

    expect($notApplied)->toBe([], 'Corrections declared in ACCESSIBILITY-DELTA.md but not applied to colors.css.');
});

test('each correction reaches the contrast ratio the delta document claims', function (): void {
    $production = tokenMap(resource_path('css/tokens/colors.css'));
    $corrections = authorisedCorrections();

    expect($corrections)->toHaveCount(9,
        'ACCESSIBILITY-DELTA.md must record 9 corrections: the 7 from design-system.md section 6, '
        .'plus A11Y-08 and A11Y-09, which Phase 3 found by running axe over the built components.');

    // The background each correction is measured against. Taken from the delta document's usage
    // column; kept here as hex because two of them are themselves tokens whose value is under test.
    $backgrounds = [
        'A11Y-01' => 'FFFFFF',
        'A11Y-02' => 'FFFFFF',
        'A11Y-03' => 'FFFFFF',
        'A11Y-04' => 'FFFFFF',
        'A11Y-05' => 'FEE2E2',
        'A11Y-06' => 'FEF3C7',
        'A11Y-07' => 'FFFFFF',
        // Not white, which is exactly why §6 missed these two. A11Y-08 measures a white label
        // against the accent fill; A11Y-09 measures secondary text against the tinted tab track.
        'A11Y-08' => '10B981',
        'A11Y-09' => 'F1F5F9',
    ];

    $problems = [];

    foreach ($corrections as $token => $row) {
        $background = $backgrounds[$row['id']] ?? 'FFFFFF';

        // A11Y-08 corrects a FILL rather than a text colour: the pairing is white-on-the-fill, so
        // both measurements are taken against white. Contrast is symmetric, so this is the same
        // computation with the roles swapped.
        if ($row['id'] === 'A11Y-08') {
            $before = contrastRatio('FFFFFF', $row['was']);
            $after = contrastRatio('FFFFFF', $row['becomes']);
        } else {
            $before = contrastRatio($row['was'], $background);
            $after = contrastRatio($row['becomes'], $background);
        }

        if (abs($before - $row['before']) > 0.02) {
            $problems[] = sprintf('%s before: computed %.2f, document says %.2f', $row['id'], $before, $row['before']);
        }

        if (abs($after - $row['after']) > 0.02) {
            $problems[] = sprintf('%s after: computed %.2f, document says %.2f', $row['id'], $after, $row['after']);
        }

        if ($after < $row['required']) {
            $problems[] = sprintf('%s still fails: %.2f against a %.2f requirement', $row['id'], $after, $row['required']);
        }

        // The property named in the document has to exist in the production layer, whether it was
        // changed in place or added.
        if (! array_key_exists('--'.$token, $production)) {
            $problems[] = sprintf('%s names --%s, which is not in colors.css', $row['id'], $token);
        }
    }

    expect($problems)->toBe([]);
});

test('the brand green survives for non-text use', function (): void {
    $production = tokenMap(resource_path('css/tokens/colors.css'));

    // §6 is explicit: #10B981 remains the brand green for chart fills, progress bars, the active
    // sidebar indicator, switch fills, and badge backgrounds. Darkening it globally would have been
    // the easy fix and would have changed the brand.
    expect($production['--track-green'] ?? null)->toBe('#10B981')
        ->and($production['--green-500'] ?? null)->toBe('#10B981')
        ->and($production['--chart-1'] ?? null)->toBe('var(--track-green)');
});

test('the token count is unchanged', function (): void {
    $approved = 0;
    $production = 0;

    foreach (['colors', 'typography', 'spacing'] as $group) {
        $approved += count(tokenMap(base_path("design-system/tokens/{$group}.css")));
        $production += count(tokenMap(resource_path("css/tokens/{$group}.css")));
    }

    // Production may add tokens — three §6 corrections needed palette steps the approved scales did
    // not contain — but may never lose one.
    expect($production)->toBeGreaterThanOrEqual($approved);
});

test('the Bootstrap bridge still mirrors the tokens it duplicates', function (): void {
    // NoHardcodedVisualValuesTest exempts _bootstrap-bridge.scss, because Sass resolves variables at
    // compile time and cannot read a CSS custom property. That exemption is only safe if something
    // checks the duplicated values, which is this test: the bridge is the one place in the system
    // where a colour appears twice, and nothing else would notice the two drifting apart.
    $bridge = (string) file_get_contents(resource_path('css/_bootstrap-bridge.scss'));
    $tokens = tokenMap(resource_path('css/tokens/colors.css'));

    /** Resolve a token whose value is itself a var() reference, down to a hex. */
    $resolve = function (string $name) use (&$resolve, $tokens): ?string {
        $value = $tokens['--'.ltrim($name, '-')] ?? null;

        if ($value === null) {
            return null;
        }

        if (preg_match('/^var\(\s*(--[a-z0-9-]+)\s*\)$/i', $value, $match) === 1) {
            return $resolve($match[1]);
        }

        return str_starts_with($value, '#') ? mb_strtoupper($value) : null;
    };

    // Every Sass variable in the bridge that is meant to carry a token, and the token it carries.
    // $success, $danger and $warning deliberately carry the §6-corrected values, not the approved
    // ones: Bootstrap uses them for text as well as fills (.text-success, .btn-success), and a
    // value that is safe as a fill but not as text fails the AA gate the moment a utility is used.
    $mapping = [
        'primary' => 'track-blue',
        'secondary' => 'slate-blue-600',
        'success' => 'green-600',
        'danger' => 'red-700',
        'warning' => 'amber-700',
        'info' => 'blue-500',
        'body-bg' => 'track-bg',
        'body-color' => 'track-dark',
        'border-color' => 'gray-200',
        'input-focus-border-color' => 'border-focus',
        'input-placeholder-color' => 'track-gray',
        'blockquote-footer-color' => 'track-gray',
    ];

    $problems = [];

    foreach ($mapping as $variable => $token) {
        if (preg_match('/^\$'.preg_quote($variable, '/').':\s*([^;!]+)/mi', $bridge, $match) !== 1) {
            $problems[] = "\${$variable} is not declared in the bridge";

            continue;
        }

        $declared = mb_strtoupper(trim($match[1]));
        $expected = $resolve($token);

        if ($expected === null) {
            $problems[] = "--{$token} does not resolve to a hex in colors.css";

            continue;
        }

        if ($declared !== $expected) {
            $problems[] = sprintf('$%s is %s but --%s is %s', $variable, $declared, $token, $expected);
        }
    }

    expect($problems)->toBe([]);
});
