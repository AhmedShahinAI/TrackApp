<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| US7 — lists paginate in the reader's language, through the app's own pager
|--------------------------------------------------------------------------
|
| The framework's stock view rendered until the T265 axe sweep: Tailwind-styled, worded "« Previous"
| on Arabic pages, and announcing its disabled step as a labelled span.
*/

test('a paginated list speaks the reader\'s language', function (string $locale, string $previous, string $next): void {
    seedScreens();

    $html = (string) renderScreen('students', $locale)->assertOk()->getContent();

    expect($html)->toContain('class="track-pager"')
        ->and($html)->toContain($previous)
        ->and($html)->toContain($next)
        ->and($html)->not->toContain('&laquo;')
        ->and($html)->not->toContain('pagination.previous');
})->with([
    'ar' => ['ar', 'السابق', 'التالي'],
    'en' => ['en', 'Previous', 'Next'],
]);

test('the pager\'s chevrons point the way the list reads', function (string $locale, string $forward): void {
    seedScreens();

    $html = (string) renderScreen('students', $locale)->assertOk()->getContent();
    $pager = substr($html, (int) strpos($html, 'class="track-pager"'));

    expect($pager)->toMatch('/rel="next">\s*\S+<svg[^>]*>.*?#'.$forward.'"/s');
})->with([
    'ar' => ['ar', 'chevron-left'],
    'en' => ['en', 'chevron-right'],
]);
