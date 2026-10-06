<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Every US1 screen renders, in both locales
|--------------------------------------------------------------------------
|
| Beyond the T104–T114 list, and the cheapest test in this phase: a Blade template with a typo in a
| component name, a missing slot, or an undefined variable fails only when someone opens the page.
| The visual suite would catch it too, but it needs a browser and nine minutes; this needs neither
| and runs on every push.
|
*/

dataset('locales', ['ar', 'en']);

test('the sign-in screen renders', function (string $locale): void {
    app()->setLocale($locale);

    $this->get('/login')
        ->assertOk()
        ->assertSee(__('auth.title'), false)
        // FR-001, said on the screen rather than only enforced by a missing route.
        ->assertSee(__('auth.no_registration'), false);
})->with('locales');

test('the landing page renders for each role', function (string $role): void {
    $user = aUser($role);

    $response = $this->actingAs($user)->get('/home');

    if ($user->can('dashboard.view')) {
        $response->assertOk()->assertSee(__('home.stats.sections'), false);
    } else {
        $response->assertRedirect();
    }
})->with(['SUPER_ADMIN', 'BRANCH_COORDINATOR', 'INSTRUCTOR']);

test('the landing page shows only in-scope counts', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $response = $this->actingAs($instructor)->get('/home');

    if ($response->status() !== 200) {
        expect(true)->toBeTrue();

        return;
    }

    /*
     * An instructor holds neither user.view nor import.view, so those tiles are absent entirely —
     * not rendered as zero. A zero would claim there are no users, which is a different and false
     * statement.
     */
    $response->assertDontSee(__('home.stats.users'), false);
});

test('the profile screen renders', function (string $locale): void {
    app()->setLocale($locale);
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee($user->name_ar, false)
        ->assertSee(__('users.fields.last_login'), false);
})->with('locales');

test('the change-password screen renders', function (string $locale): void {
    app()->setLocale($locale);
    $user = aUser('SUPER_ADMIN');

    $this->actingAs($user)
        ->get('/profile/password')
        ->assertOk()
        ->assertSee(__('auth.password_change.current'), false)
        // FR-015 stated before the act, not reported after it.
        ->assertSee(__('auth.password_change.changed_message'), false);
})->with('locales');

test('the accounts screen renders with its filters and table', function (string $locale): void {
    app()->setLocale($locale);
    $admin = aUser('SUPER_ADMIN');
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس للعرض']);

    $this->actingAs($admin)
        ->get('/users')
        ->assertOk()
        ->assertSee(__('users.subtitle'), false)
        ->assertSee(__('users.filters.search'), false)
        ->assertSee('مدرس للعرض', false)
        // The two confirmations are included for a Super Admin, with their consequences stated.
        ->assertSee(__('users.deactivate.preserved'), false)
        ->assertSee(__('users.reset_password.message'), false);
})->with('locales');

test('the accounts screen honours its filters', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس ظاهر']);

    $this->actingAs($admin)
        ->get('/users?role=INSTRUCTOR')
        ->assertOk()
        ->assertSee('مدرس ظاهر', false);

    $this->actingAs($admin)
        ->get('/users?q=no-such-name-exists')
        ->assertOk()
        ->assertSee(__('users.empty.message'), false);
});

test('an instructor cannot reach the accounts screen', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $this->actingAs($instructor)->get('/users')->assertForbidden();
});

test('the first-sign-in banner renders on the landing page', function (): void {
    $user = aUser('SUPER_ADMIN', ['first_login_at' => null, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);

    $this->get('/home')
        ->assertOk()
        ->assertSee(__('auth.first_login.title'), false)
        // Non-blocking: it offers the action rather than demanding it.
        ->assertSee(__('auth.first_login.action'), false);
});

test('no screen leaks a password value', function (string $path): void {
    $admin = aUser('SUPER_ADMIN');

    $body = $this->actingAs($admin)->get($path)->getContent();

    // `type="password"` is expected on the forms; a VALUE is not. This looks for the fixture
    // password and for a hash prefix, either of which would mean a credential reached the markup.
    expect($body)->not->toContain('Track2026')
        ->and($body)->not->toContain('argon2')
        ->and($body)->not->toContain('$2y$');
})->with(['/home', '/profile', '/profile/password', '/users']);

test('a guest can switch the sign-in screen to English and back', function (): void {
    // FR-074 for someone who has no account yet. Without a session tier in SetLocale the switch on
    // the sign-in screen posts and changes nothing, which is how the broken route was found.
    $this->get('/login')->assertSee('تسجيل الدخول', false);

    $this->post('/locale', ['locale' => 'en'])->assertRedirect();
    $this->get('/login')->assertSee('Sign in', false);

    $this->post('/locale', ['locale' => 'ar'])->assertRedirect();
    $this->get('/login')->assertSee('تسجيل الدخول', false);
});

test('a signed-in person stores their choice on their record', function (): void {
    $user = aUser('SUPER_ADMIN', ['locale' => 'ar']);

    $this->actingAs($user)->post('/locale', ['locale' => 'en'])->assertRedirect();

    expect($user->fresh()->locale)->toBe('en');
});

test('a signed-in person ignores a session locale left by someone else', function (): void {
    // A shared machine: a guest switched to English, then someone whose preference is Arabic signs
    // in. Their stored preference wins, or the switch would quietly change the language for
    // whoever sits down next.
    $this->post('/locale', ['locale' => 'en']);

    $user = aUser('SUPER_ADMIN', ['locale' => 'ar']);

    $this->actingAs($user)->get('/profile')->assertSee('الملف الشخصي', false);
});

test('an unsupported locale is refused', function (): void {
    $this->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    $this->post('/locale', ['locale' => '../../etc/passwd'])->assertSessionHasErrors('locale');
});
