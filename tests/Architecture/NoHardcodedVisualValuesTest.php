<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T065 — constitution IV extended to the visual layer
|--------------------------------------------------------------------------
|
| No hex colour, px radius, px shadow, or transition duration in a .blade.php or .scss file outside
| resources/css/tokens/. The token layer defines; everything else consumes.
|
| This is the guard that keeps the design system a system. Without it the first screen under time
| pressure writes `#0F4C5C` inline, the second copies it, and six screens later the palette exists
| in two places — one of which nobody updates when the brand changes.
|
| What is deliberately NOT banned: px for a measurement no token expresses (a 240px sidebar is
| --sidebar-width, but a 420px max-width on a search field is a layout decision), and px in a media
| query, where a custom property cannot be used at all.
|
*/

/**
 * @return list<string>
 */
function visualLayerFiles(): array
{
    $files = [];

    $finder = Finder::create()
        ->files()
        ->in([resource_path('css'), resource_path('views')])
        // The token layer is where these values are supposed to live.
        ->notPath('tokens')
        // The Bootstrap bridge is the one file that MUST hold literals: Sass cannot read a CSS
        // custom property at compile time, and Bootstrap's variables are resolved at compile time.
        // It is not unchecked — TokenParityTest asserts every value in it still matches the token
        // it mirrors, which is the only thing that could go wrong there.
        ->notName('_bootstrap-bridge.scss')
        // Dev-only fixture surfaces. The component gallery is specimen markup whose whole purpose
        // is to show every component with representative content; running it through the
        // user-facing-copy rules would mean translating a test fixture.
        ->notPath('dev')
        ->name(['*.scss', '*.blade.php']);

    foreach ($finder as $file) {
        $files[] = (string) $file->getRealPath();
    }

    return $files;
}

/**
 * Strip the places a literal is legitimate before scanning.
 */
function scannableVisualSource(string $contents): string
{
    return (string) preg_replace([
        '#/\*.*?\*/#s',        // CSS and Sass block comments
        '#^\s*//.*$#m',        // Sass line comments
        '#\{\{--.*?--\}\}#s',  // Blade comments
        '#@media[^{]*\{#',     // media query conditions — px is unavoidable in a breakpoint
    ], ' ', $contents);
}

test('no hex colour outside the token layer', function (): void {
    $offenders = [];

    foreach (visualLayerFiles() as $path) {
        $source = scannableVisualSource((string) file_get_contents($path));

        // 3-, 4-, 6- and 8-digit hex. The word boundary before # is unnecessary but the one after
        // matters, so `#0F4C5C` is caught and an id selector like `#track-content` is not.
        if (preg_match_all('/#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})\b/i', $source, $matches) > 0) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $offenders[] = $relative.': '.implode(', ', array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe([], 'Hex colours belong in resources/css/tokens/, referenced as var(--token).');
});

test('no px radius, shadow, or duration outside the token layer', function (): void {
    $offenders = [];

    $patterns = [
        'radius' => '/border-radius\s*:\s*[^;]*\d+px/i',
        'shadow' => '/box-shadow\s*:\s*[^;]*\d+px/i',
        'duration' => '/(transition|animation)(-duration)?\s*:\s*[^;]*\d+m?s\b/i',
    ];

    foreach (visualLayerFiles() as $path) {
        $source = scannableVisualSource((string) file_get_contents($path));
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

        foreach ($patterns as $kind => $pattern) {
            if (preg_match($pattern, $source, $match) === 1) {
                $offenders[] = sprintf('%s: %s "%s"', $relative, $kind, trim($match[0]));
            }
        }
    }

    expect($offenders)->toBe([], 'Use --radius-*, --shadow-*, and --dur-* instead of literals.');
});

test('no rgb or rgba colour literal outside the token layer', function (): void {
    // A hex ban alone is trivially sidestepped by writing rgb(15 76 92). The two exceptions are the
    // white overlays the sidebar needs for hover and its active surface, which the design system
    // itself expresses as `rgba(255,255,255,.12)` — there is no token for "white at 8%".
    $allowedPattern = '/rgba?\(\s*255[\s,]+255[\s,]+255/i';

    $offenders = [];

    foreach (visualLayerFiles() as $path) {
        $source = scannableVisualSource((string) file_get_contents($path));

        if (preg_match_all('/rgba?\([^)]*\)/i', $source, $matches) === 0) {
            continue;
        }

        $bad = array_values(array_filter(
            array_unique($matches[0]),
            static fn (string $value): bool => preg_match($allowedPattern, $value) !== 1,
        ));

        if ($bad !== []) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $offenders[] = $relative.': '.implode(', ', $bad);
        }
    }

    expect($offenders)->toBe([]);
});

test('the scanner catches what it claims to catch', function (): void {
    // Without this, all three tests above pass on an empty file list and keep passing after the
    // first screen hard-codes a colour.
    expect(visualLayerFiles())->not->toBeEmpty();

    $violation = scannableVisualSource('.x { color: #0F4C5C; border-radius: 12px; box-shadow: 0 1px 3px rgba(1,2,3,.1); transition: all 180ms; }');

    expect(preg_match('/#([0-9a-f]{6})\b/i', $violation))->toBe(1)
        ->and(preg_match('/border-radius\s*:\s*[^;]*\d+px/i', $violation))->toBe(1)
        ->and(preg_match('/box-shadow\s*:\s*[^;]*\d+px/i', $violation))->toBe(1)
        ->and(preg_match('/(transition|animation)(-duration)?\s*:\s*[^;]*\d+m?s\b/i', $violation))->toBe(1);

    // And that it does not fire on the legitimate forms.
    $clean = scannableVisualSource('.y { color: var(--track-blue); border-radius: var(--radius-lg); } @media (max-width: 767.98px) { .z { padding: var(--space-4); } }');

    expect(preg_match('/#([0-9a-f]{6})\b/i', $clean))->toBe(0)
        ->and(preg_match('/border-radius\s*:\s*[^;]*\d+px/i', $clean))->toBe(0);

    // A comment is not code. The token files are full of hex values inside comments explaining them.
    $commented = scannableVisualSource("// --track-blue is #0F4C5C\n.a { color: var(--track-blue); }");

    expect(preg_match('/#([0-9a-f]{6})\b/i', $commented))->toBe(0);
});
