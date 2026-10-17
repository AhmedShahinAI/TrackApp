<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SEC-065 — a user's hard delete cannot rewrite who did what (security plan G-21)
|--------------------------------------------------------------------------
*/

test('a user with audit entries cannot be hard-deleted, and the entries keep their author', function (): void {
    $user = aUser('INSTRUCTOR');
    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);

    $entries = DB::table('audit_logs')->where('user_id', $user->id)->count();
    expect($entries)->toBeGreaterThan(0);

    expect(fn () => DB::table('users')->where('id', $user->id)->delete())->toThrow(QueryException::class);

    expect(DB::table('audit_logs')->where('user_id', $user->id)->count())->toBe($entries);
});

test('a soft delete, the way the application removes users, is unaffected', function (): void {
    $user = aUser('INSTRUCTOR');
    $this->post('/login', ['email' => $user->email, 'password' => 'Track2026']);

    $user->delete();

    expect(User::withTrashed()->find($user->id)?->trashed())->toBeTrue()
        ->and(DB::table('audit_logs')->where('user_id', $user->id)->count())->toBeGreaterThan(0);
});
