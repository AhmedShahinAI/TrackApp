<?php

declare(strict_types=1);

require_once __DIR__.'/SupportScreens.php';

/*
|--------------------------------------------------------------------------
| T443 — every support screen in both languages, and the switch works signed out (FR-161)
|--------------------------------------------------------------------------
*/

test('all eight screens render with the right lang and dir', function (string $locale, string $dir): void {
    foreach (supportScreensIn($locale) as $screen => $html) {
        // toBeTrue with a message: toContain() reads a second argument as another needle, not a label.
        expect(str_contains($html, '<html lang="'.$locale.'" dir="'.$dir.'"'))->toBeTrue("$screen in $locale");
    }
})->with([['ar', 'rtl'], ['en', 'ltr']]);

test('a visitor with no account can switch language, and the choice holds across the public pages', function (): void {
    supportWorld();

    $this->get('/support')->assertSee('lang="ar"', false);

    // The language switch on the public layout: a form posting to /locale, no sign-in.
    $this->from('/support')->post('/locale', ['locale' => 'en'])->assertRedirect('/support');

    $this->get('/support')->assertSee('<html lang="en" dir="ltr"', false);
    $this->get('/support/status')->assertSee('<html lang="en" dir="ltr"', false);

    expect(auth()->check())->toBeFalse();
});

test('?lang= on the public pages is remembered for the visit, and anything but ar or en is ignored', function (): void {
    supportWorld();

    $this->get('/support/status?lang=en')->assertSee('lang="en"', false);
    $this->get('/support')->assertSee('lang="en"', false);
    $this->get('/support?lang=fr')->assertSee('lang="en"', false);
});
