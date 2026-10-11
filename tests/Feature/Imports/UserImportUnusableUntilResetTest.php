<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| T339 — every created account is refused sign-in until a Super Admin reset (FR-105, SC-024)
|--------------------------------------------------------------------------
*/

test('every account the import creates is refused sign-in, whatever password is tried', function (): void {
    $admin = aUser('SUPER_ADMIN');

    importBulk($admin, 'users', staffFile([
        ['مدرس أول', 'First', 'first@mu.edu.sa', 'INSTRUCTOR', ''],
        ['مدرس ثان', 'Second', 'second@mu.edu.sa', 'INSTRUCTOR', ''],
        ['منسق', 'Coordinator', 'admin2@mu.edu.sa', 'INSTRUCTOR', ''],
    ]));

    $created = User::query()->whereIn('email', ['first@mu.edu.sa', 'second@mu.edu.sa', 'admin2@mu.edu.sa'])->get();

    expect($created)->toHaveCount(3);

    foreach ($created as $user) {
        expect($user->first_login_at)->toBeNull()
            ->and($user->password)->not->toBe('')
            // A real hash of a secret nobody was told: no guessable password opens it.
            ->and(Hash::check('Track2026', $user->password))->toBeFalse();

        $this->postJson('/api/v1/auth/logout');

        foreach (['', 'Track2026', $user->email, 'password', $user->name_en] as $attempt) {
            expect($this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => $attempt])->status())
                ->not->toBe(200);
        }
    }
});

test('a Super Admin reset sets a real password, and only then can the account sign in', function (): void {
    $admin = aUser('SUPER_ADMIN');
    importBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'teacher@mu.edu.sa', 'INSTRUCTOR', '']]));
    $user = User::query()->where('email', 'teacher@mu.edu.sa')->sole();

    $this->actingAs($admin)
        ->postJson(route('api.v1.users.reset-password', $user), ['password' => 'Track2026'])
        ->assertOk();

    $this->postJson('/api/v1/auth/logout');
    app('auth')->forgetGuards();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Track2026'])
        ->assertOk()
        ->assertJsonPath('data.first_login', true);
});
