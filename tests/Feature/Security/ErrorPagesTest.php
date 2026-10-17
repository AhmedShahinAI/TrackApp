<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEC-074 — 419, 429 and 500 are the platform's own pages, in both languages (security plan G-12)
|--------------------------------------------------------------------------
|
| Rendered through the real exception handler from throwaway routes, so the test sees what a visitor
| would: the status, the translated wording, the security headers, and no exception detail.
|
*/

beforeEach(function (): void {
    Route::middleware('web')->get('__err/419', fn () => abort(419));
    Route::middleware('web')->get('__err/429', fn () => abort(429));
    Route::middleware('web')->get('__err/500', fn () => throw new RuntimeException('secret-internal-detail'));
    Route::getRoutes()->refreshNameLookups();
    config(['app.debug' => false]);
});

test('each error page speaks the platform\'s words and leaks nothing', function (int $status, string $title): void {
    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        $response = $this->withSession(['locale' => $locale])->get('/__err/'.$status);

        $response->assertStatus($status)
            ->assertSee(__($title), false)
            ->assertDontSee('secret-internal-detail')
            ->assertDontSee('Symfony')
            ->assertDontSee('vendor/laravel');

        expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
    }
})->with([
    'session expired' => [419, 'common.errors.sessionExpired'],
    'too many requests' => [429, 'common.errors.tooMany'],
    'server error' => [500, 'common.errors.server'],
]);
