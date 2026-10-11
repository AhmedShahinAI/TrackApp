<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T259 — every screen renders in Arabic RTL and in English LTR (FR-074, FR-076)
|--------------------------------------------------------------------------
|
| Three facts per screen and locale, each of which has broken independently elsewhere: the `lang`
| attribute (what a screen reader pronounces with), the `dir` attribute (what the browser mirrors
| by), and the stylesheet (the RTL build of Bootstrap or the LTR one). A page can carry `dir="rtl"`
| and the LTR stylesheet, and every margin is then on the wrong side while the text reads correctly
| — which looks like a styling bug rather than the localisation bug it is.
|
*/

test('every screen carries the right lang, dir and stylesheet in both locales', function (string $key): void {
    seedScreens();

    foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction) {
        $html = (string) renderScreen($key, $locale)->assertOk()->getContent();

        expect($html)->toMatch('/<html[^>]*\blang="'.$locale.'"/', "{$key} · {$locale}: lang")
            ->and($html)->toMatch('/<html[^>]*\bdir="'.$direction.'"/', "{$key} · {$locale}: dir")
            ->and($html)->toMatch('/<link[^>]+href="[^"]*app-'.$direction.'[^"]*\.css"/', "{$key} · {$locale}: stylesheet")
            ->and($html)->not->toMatch('/<link[^>]+href="[^"]*app-'.($direction === 'rtl' ? 'ltr' : 'rtl').'[^"]*\.css"/');
    }
})->with(array_keys(screens()));

test('every GET screen route is in the sweep or excluded with a reason', function (): void {
    $swept = array_values(array_unique(array_map(static fn (array $s): string => $s[0], screens())));
    $excluded = array_keys(screensExcluded());

    $screenRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RouteDefinition $route): bool => in_array('GET', $route->methods(), true)
            && $route->getName() !== null
            && ! str_starts_with($route->uri(), 'api/')
            && ! str_starts_with($route->getName(), 'sanctum.')
            && ! str_starts_with($route->getName(), 'storage.'))
        ->map(fn (RouteDefinition $route): string => (string) $route->getName())
        ->values()
        ->all();

    $unaccounted = array_values(array_diff($screenRoutes, $swept, $excluded));

    expect($unaccounted)->toBe([], 'Add these routes to tests/Support/Screens.php, or exclude them with a reason.');
});
