<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T358 — the proof-of-work solver reaches no external origin (research.md R-08)
|--------------------------------------------------------------------------
|
| R-08 chose a self-hosted challenge precisely so that no third party receives a request from the
| support form (PDPL) and the CSP needs no exemption (T359). The solver is resources/js/pow — the
| puzzle, the worker and the driver. This keeps every file there that way: a later "upgrade" that
| pulled in a script, a font or an analytics call from anywhere fails here before it ships.
|
| The directory is scanned, not a list of names, so a file added beside the three is held to the
| same rule without anyone remembering to register it.
|
*/

/**
 * @return array<string, array{string}>
 */
function powSolverFiles(): array
{
    $files = [];

    // Built from __DIR__, not resource_path(): a dataset is collected before the application boots.
    foreach (glob(dirname(__DIR__, 2).'/resources/js/pow/*.js') ?: [] as $path) {
        $files[basename($path)] = [$path];
    }

    return $files;
}

/**
 * The code with comments removed and string contents kept. Comments may mention a URL; code may not.
 */
function powCode(string $path): string
{
    $source = (string) file_get_contents($path);

    // Block comments, then line comments that are not inside a string or a regular-expression
    // literal ("//" after a quote, a colon or a slash is part of a value, and is checked below).
    return (string) preg_replace(['#/\*.*?\*/#s', '#(?<![:"\'`/\\\\])//[^\n]*#'], '', $source);
}

test('the solver directory holds the three files the review settled on', function (): void {
    expect(array_keys(powSolverFiles()))->toEqualCanonicalizing(['puzzle.js', 'solve.js', 'worker.js']);
});

test('a solver file names no origin and loads nothing', function (string $path): void {
    $code = powCode($path);

    $forbidden = [
        // Absolute and protocol-relative origins, in any quote style.
        'an absolute URL' => '#https?://#i',
        'a protocol-relative URL' => '#["\'`]//#',
        // A blob: or data: worker is exactly what the CSP refuses (worker-src → script-src 'self').
        'a blob: or data: URL' => '#["\'`](blob|data):#i',
        'createObjectURL' => '#createObjectURL#',
        // No network, no code loaded at run time.
        'fetch()' => '#\bfetch\s*\(#',
        'a dynamic import()' => '#\bimport\s*\(#',
        'importScripts' => '#importScripts#',
        'XMLHttpRequest' => '#XMLHttpRequest#',
        'sendBeacon' => '#sendBeacon#',
        'WebSocket' => '#WebSocket#',
        'EventSource' => '#EventSource#',
    ];

    foreach ($forbidden as $what => $pattern) {
        expect(preg_match($pattern, $code))->toBe(0, basename($path)." must not use {$what}.");
    }
})->with(powSolverFiles());

test('the driver starts its worker from a fixed sibling file that Vite emits on our origin', function (): void {
    $code = powCode(resource_path('js/pow/solve.js'));

    // new URL(..., import.meta.url) is what makes Vite emit worker.js as its own hashed asset
    // (vite.config.js `worker.format`); the bundle then loads /build/assets/worker-<hash>.js.
    expect($code)->toContain("new Worker(new URL('./worker.js', import.meta.url), { type: 'module' })")
        ->and(substr_count($code, 'new Worker('))->toBe(1)
        // The URL is not a parameter: no caller can point the worker anywhere else.
        ->and(str_contains($code, 'workerUrl'))->toBeFalse();
});

test('the worker and the in-page fallback run the same search', function (): void {
    expect(powCode(resource_path('js/pow/worker.js')))->toContain("from './puzzle.js'")
        ->and(powCode(resource_path('js/pow/solve.js')))->toContain("from './puzzle.js'");
});

test('the old two-copy solver is gone', function (): void {
    expect(is_file(public_path('js/pow-worker.js')))->toBeFalse()
        ->and(is_dir(resource_path('js/vendor/pow')))->toBeFalse();
});
