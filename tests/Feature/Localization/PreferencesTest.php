<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T274 — the language preference, set on the profile, kept on the account (FR-074)
|--------------------------------------------------------------------------
*/

test('the preferences page offers both languages, the current one pressed', function (): void {
    $user = aUser('INSTRUCTOR', ['locale' => 'ar']);

    $html = (string) $this->actingAs($user)->get(route('profile.preferences'))->assertOk()->getContent();

    expect($html)->toContain('aria-pressed="true"')
        ->and($html)->toMatch('/<button[^>]*value="ar"[^>]*aria-pressed="true"/s')
        ->and($html)->toMatch('/<button[^>]*value="en"[^>]*aria-pressed="false"/s');
});

test('choosing English saves it to the account, and the next page is in English', function (): void {
    $user = aUser('INSTRUCTOR', ['locale' => 'ar']);

    $this->actingAs($user)
        ->from(route('profile.preferences'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('profile.preferences'));

    expect($user->fresh()->locale)->toBe('en');

    $this->actingAs($user->fresh())->get(route('profile.preferences'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr">', false)
        ->assertSee(__('home.preferences.title', [], 'en'));
});

test('a language outside the two is refused', function (): void {
    $user = aUser('INSTRUCTOR', ['locale' => 'ar']);

    $this->actingAs($user)->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBe('ar');
});

test('the topbar and the sign-in screen carry the same segmented switch', function (): void {
    $this->actingAs(aUser('INSTRUCTOR'))->get(route('home'))
        ->assertOk()
        ->assertSee('track-segmented', false);

    app('auth')->forgetGuards();
    $this->flushSession();

    $this->get(route('login'))->assertOk()->assertSee('track-segmented', false);
});

test('switching language returns to the page it was on, and never to another host', function (): void {
    $user = aUser('SUPER_ADMIN');

    // Same host: back to where the switch was pressed.
    $this->actingAs($user)->from(route('terms.index'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('terms.index'));

    // A Referer pointing elsewhere is caller input; the redirect stays home.
    $this->actingAs($user)->withHeader('Referer', 'https://evil.example/phish')
        ->post(route('locale.update'), ['locale' => 'ar'])
        ->assertRedirect(route('home'));
});
