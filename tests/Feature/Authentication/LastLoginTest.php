<?php

declare(strict_types=1);

use App\Modules\Shared\Support\Format;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| T351 — last_login_at: moved by every successful sign-in, by nothing else (FR-113)
|--------------------------------------------------------------------------
|
| first_login_at is a historical fact, set once (FirstLoginTest). last_login_at is the one that
| moves — and only a sign-in that actually succeeds may move it, or the accounts list would report
| as "last signed in" a moment when somebody merely typed the wrong password at that address.
|
*/

/** @return Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response> */
function attemptSignIn(string $email, string $password): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
}

test('every successful sign-in moves last_login_at, and first_login_at stays where it was', function (): void {
    Carbon::setTestNow('2026-10-01 08:00:00');
    $user = aUser('INSTRUCTOR', ['first_login_at' => null, 'last_login_at' => null]);

    attemptSignIn($user->email, 'Track2026')->assertOk();
    $first = $user->fresh();

    expect($first->last_login_at?->toDateTimeString())->toBe('2026-10-01 08:00:00')
        ->and($first->first_login_at?->toDateTimeString())->toBe('2026-10-01 08:00:00');

    test()->postJson('/api/v1/auth/logout');
    app('auth')->forgetGuards();
    Carbon::setTestNow('2026-10-03 13:30:00');

    attemptSignIn($user->email, 'Track2026')->assertOk();
    $second = $user->fresh();

    expect($second->last_login_at?->toDateTimeString())->toBe('2026-10-03 13:30:00')
        ->and($second->first_login_at?->toDateTimeString())->toBe('2026-10-01 08:00:00');

    Carbon::setTestNow();
});

test('a failed attempt does not touch it — wrong password, unknown email, or a deactivated account', function (): void {
    $stamp = Carbon::parse('2026-09-20 10:00:00');
    $user = aUser('INSTRUCTOR', ['last_login_at' => $stamp]);
    $inactive = aUser('INSTRUCTOR', ['last_login_at' => $stamp, 'is_active' => false]);

    attemptSignIn($user->email, 'not-the-password')->assertUnprocessable();
    attemptSignIn('nobody@mu.edu.sa', 'Track2026')->assertUnprocessable();
    attemptSignIn($inactive->email, 'Track2026')->assertForbidden();

    expect($user->fresh()->last_login_at?->equalTo($stamp))->toBeTrue()
        ->and($inactive->fresh()->last_login_at?->equalTo($stamp))->toBeTrue();
});

test('the browser sign-in form moves it too, to the moment of the sign-in', function (): void {
    Carbon::setTestNow('2026-10-04 11:20:00');
    $user = aUser('INSTRUCTOR', ['last_login_at' => null]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Track2026'])->assertRedirect();

    expect($user->fresh()->last_login_at?->toDateTimeString())->toBe('2026-10-04 11:20:00');
    Carbon::setTestNow();
});

test('a throttled attempt does not touch it, even with the right password', function (): void {
    $stamp = Carbon::parse('2026-09-20 10:00:00');
    $user = aUser('INSTRUCTOR', ['last_login_at' => $stamp]);

    foreach (range(1, 5) as $ignored) {
        attemptSignIn($user->email, 'wrong-password')->assertUnprocessable();
    }

    attemptSignIn($user->email, 'Track2026')->assertStatus(429);

    expect($user->fresh()->last_login_at?->equalTo($stamp))->toBeTrue();
});

test('an account the staff import created is refused, and records no sign-in', function (): void {
    $admin = aUser('SUPER_ADMIN');
    importBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'imported@mu.edu.sa', 'INSTRUCTOR', '']]));
    $this->postJson('/api/v1/auth/logout');
    app('auth')->forgetGuards();

    attemptSignIn('imported@mu.edu.sa', 'Track2026')->assertUnprocessable();

    expect(App\Models\User::query()->where('email', 'imported@mu.edu.sa')->sole()->last_login_at)->toBeNull();
});

test('a sign-in is not an edit: updated_at stays, and no user.updated entry is written', function (): void {
    $user = aUser('INSTRUCTOR');
    $edited = $user->fresh()->updated_at;
    Illuminate\Support\Facades\DB::table('audit_logs')->delete();
    $this->travel(5)->minutes();

    attemptSignIn($user->email, 'Track2026')->assertOk();

    expect($user->fresh()->updated_at?->equalTo($edited))->toBeTrue()
        ->and(Illuminate\Support\Facades\DB::table('audit_logs')->pluck('action')->all())->toBe(['auth.login']);
});

test('"first" is decided by the database: a first sign-in already recorded by a concurrent request is not first again', function (): void {
    $user = aUser('INSTRUCTOR', ['first_login_at' => null]);

    // The other request wins the race after this one has loaded the row but before it writes.
    App\Models\User::retrieved(function (App\Models\User $loaded) use ($user): void {
        if ($loaded->is($user) && $loaded->first_login_at === null) {
            Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['first_login_at' => '2026-10-01 07:00:00']);
        }
    });

    attemptSignIn($user->email, 'Track2026')->assertOk()->assertJsonPath('data.first_login', false);

    expect($user->fresh()->first_login_at?->toDateTimeString())->toBe('2026-10-01 07:00:00');
});

test('if the sign-in cannot be recorded, it does not stand: the session is signed out again', function (): void {
    $user = aUser('INSTRUCTOR', ['last_login_at' => null]);

    // The audit store becomes unavailable: the `auth.login` insert fails inside the sign-in's
    // transaction, as a deadlock or a timeout on it would.
    Illuminate\Support\Facades\Schema::rename('audit_logs', 'audit_logs_unavailable');

    $this->withoutExceptionHandling();

    expect(fn () => attemptSignIn($user->email, 'Track2026'))->toThrow(Illuminate\Database\QueryException::class);

    expect(auth()->guard('web')->check())->toBeFalse()
        ->and($user->fresh()->last_login_at)->toBeNull();
});

test('a sign-in at midnight UTC is shown in the display timezone, not as a calendar date', function (): void {
    expect(Format::dateTime(Carbon::parse('2026-10-02 00:00:00', 'UTC')))
        ->toBe(Format::dateTime(Carbon::parse('2026-10-02 00:00:01', 'UTC')->subSecond()->setTimezone('UTC')))
        ->and(Format::dateTime(Carbon::parse('2026-10-02 00:00:00', 'UTC')))->toContain('03:00');
});

test('it renders on the accounts list as «آخر دخول», through Format::dateTime, and "never" when null', function (string $locale): void {
    $admin = aUser('SUPER_ADMIN', ['locale' => $locale]);
    $seen = aUser('INSTRUCTOR', ['name_ar' => 'مدرس دخل', 'last_login_at' => Carbon::parse('2026-10-02 09:15:00')]);
    aUser('INSTRUCTOR', ['name_ar' => 'مدرس لم يدخل', 'last_login_at' => null]);

    $this->actingAs($admin)->withSession(['locale' => $locale])->get(route('users.index'))
        ->assertOk()
        ->assertSee(__('users.fields.last_login', [], $locale))
        ->assertSee(Format::dateTime($seen->last_login_at))
        ->assertSee(__('users.never_signed_in', [], $locale));
})->with(['ar', 'en']);

test('it renders on the profile screen as the reference\'s «آخر دخول» line, in either language', function (string $locale): void {
    $user = aUser('INSTRUCTOR', ['locale' => $locale, 'last_login_at' => Carbon::parse('2026-10-02 09:15:00')]);

    $this->actingAs($user)->withSession(['locale' => $locale])->get(route('profile'))
        ->assertOk()
        ->assertSee(__('users.fields.last_login', [], $locale))
        ->assertSee(Format::dateTime($user->last_login_at));
})->with(['ar', 'en']);

test('the profile says "never" when there is no value, in either language', function (string $locale): void {
    $user = aUser('INSTRUCTOR', ['locale' => $locale, 'last_login_at' => null]);

    $this->actingAs($user)->withSession(['locale' => $locale])->get(route('profile'))
        ->assertOk()
        ->assertSee(__('users.never_signed_in', [], $locale));
})->with(['ar', 'en']);

/*
| Who may see it — the accounts list, by organization
*/

test('another organization\'s staff — and when they last signed in — are not listed, and their accounts are a 404', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreign = aUser('INSTRUCTOR', [
        'organization_id' => App\Models\Organization::factory()->create()->id,
        'name_ar' => 'منسوب كلية أخرى', 'email' => 'other-college@mu.edu.sa',
        'last_login_at' => Carbon::parse('2026-10-02 09:15:00'),
    ]);

    $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertDontSee('منسوب كلية أخرى');

    expect($this->actingAs($admin)->getJson(route('api.v1.users.index'))->json('data.*.email'))
        ->not->toContain('other-college@mu.edu.sa');

    $this->actingAs($admin)->putJson(route('api.v1.users.update', $foreign), ['name_ar' => 'x'])->assertNotFound();
    $this->actingAs($admin)->patchJson(route('api.v1.users.deactivate', $foreign))->assertNotFound();
    $this->actingAs($admin)->postJson(route('api.v1.users.reset-password', $foreign), ['password' => 'Track2026'])->assertNotFound();
});

test('the list is sorted by the server across pages: never-signed-in first, then the oldest', function (): void {
    $admin = aUser('SUPER_ADMIN', ['last_login_at' => Carbon::parse('2026-10-05 08:00:00')]);
    aUser('INSTRUCTOR', ['email' => 'recent@mu.edu.sa', 'last_login_at' => Carbon::parse('2026-10-04 08:00:00')]);
    aUser('INSTRUCTOR', ['email' => 'old@mu.edu.sa', 'last_login_at' => Carbon::parse('2026-01-01 08:00:00')]);
    aUser('INSTRUCTOR', ['email' => 'never@mu.edu.sa', 'last_login_at' => null]);

    $emails = fn (array $query) => $this->actingAs($admin)->getJson(route('api.v1.users.index', $query))->json('data.*.email');

    expect(array_slice($emails(['sort' => 'last_login', 'per_page' => 2]), 0, 2))->toBe(['never@mu.edu.sa', 'old@mu.edu.sa'])
        ->and($emails(['sort' => 'last_login_desc', 'per_page' => 1]))->toBe([$admin->email]);
});

test('per_page is bounded', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.users.index', ['per_page' => 100000]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
