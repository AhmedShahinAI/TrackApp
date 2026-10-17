<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;

/*
|--------------------------------------------------------------------------
| T463 — the sign-in screen links to /support once the channel is opened (FR-114)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    supportWorld();
});

test('the link is absent while support_channel_enabled is false, its default', function (): void {
    expect(Settings::get(Settings::SUPPORT_CHANNEL_ENABLED))->toBeFalse();

    $this->get('/login')->assertOk()
        ->assertDontSee(route('support.create'), false)
        ->assertDontSee(__('auth.support_link'));
});

test('the link is present, in both languages, when the flag is on', function (string $locale): void {
    supportSetting(Settings::SUPPORT_CHANNEL_ENABLED, true);

    // The sign-in page takes a visitor's language from the session the switch writes (FR-074).
    $this->withSession(['locale' => $locale])->get('/login')->assertOk()
        ->assertSee('href="'.route('support.create').'"', false)
        ->assertSee(trans('auth.support_link', [], $locale));
})->with(['ar', 'en']);

test('a deploy that has not seeded the flag still signs staff in, without the link', function (): void {
    App\Models\Setting::query()->where('key', Settings::SUPPORT_CHANNEL_ENABLED)->delete();
    Settings::forget();

    $this->get('/login')->assertOk()->assertDontSee(route('support.create'), false);
});

test('the sign-in page renders without the link when settings cannot be read at all', function (): void {
    // Phase 25 review, H-1: the cache empty and the database unreachable. SetLocale already falls back
    // for its own read; the switch must not be the thing that takes sign-in down.
    supportSetting(Settings::SUPPORT_CHANNEL_ENABLED, true);
    Settings::forget();
    Illuminate\Support\Facades\Schema::drop('settings');

    $this->get('/login')->assertOk()->assertDontSee(route('support.create'), false);
});

test('only a stored true turns the link on', function (mixed $stored): void {
    supportSetting(Settings::SUPPORT_CHANNEL_ENABLED, $stored);

    $this->get('/login')->assertOk()->assertDontSee(route('support.create'), false);
})->with([['1'], [1], ['true'], [null]]);
