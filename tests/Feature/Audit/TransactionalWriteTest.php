<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\UserManagement\Actions\CreateUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| T154 — a failed audit insert takes the business write with it (FR-030)
|--------------------------------------------------------------------------
|
| Constitution II says the business row and its audit entry commit together or not at all. The
| failure this guards against is silent and one-directional: if the audit insert throws and the
| surrounding write survives, the platform has a change nobody can account for, and it is
| discoverable only by noticing an absence.
|
| ## Why the table is dropped rather than the Auditor mocked
|
| Auditor is final, so it cannot be doubled — but that is not the reason. A mock would prove that
| *an exception from a collaborator* rolls the transaction back, which is a statement about
| DB::transaction. Removing the table makes the real insert fail for the real reason it would fail
| in production: the write the Auditor actually performs cannot complete. What is asserted then is
| the behaviour, not the plumbing.
|
*/

test('a user is not created when its audit entry cannot be written', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $before = User::query()->count();

    Schema::drop('audit_logs');

    $attempt = fn () => app(CreateUser::class)->execute([
        'name_ar' => 'د. فيصل العتيبي',
        'name_en' => 'Dr. Faisal Al-Otaibi',
        'email' => 'f.alotaibi@mu.edu.sa',
        'password' => 'Track2026',
        'role' => 'INSTRUCTOR',
    ]);

    // QueryException, not Throwable: Pest reads an interface name as a message to match.
    expect($attempt)->toThrow(QueryException::class);

    // The row is gone with the entry — not orphaned, and not half-written.
    expect(User::query()->count())->toBe($before)
        ->and(User::query()->where('email', 'f.alotaibi@mu.edu.sa')->exists())->toBeFalse();

    expect($admin->exists)->toBeTrue();
});

test('the role assignment rolls back too, not just the row', function (): void {
    $admin = aUser('SUPER_ADMIN');

    Schema::drop('audit_logs');

    try {
        app(CreateUser::class)->execute([
            'name_ar' => 'منسّق', 'name_en' => 'Coordinator',
            'email' => 'c.new@mu.edu.sa', 'password' => 'Track2026',
            'role' => 'BRANCH_COORDINATOR',
        ]);
    } catch (Throwable) {
        // Expected. What matters is what is left behind.
    }

    /*
     * The pivot writes are the part most likely to survive a partial rollback, because they happen
     * after the row exists and through a different relation. A coordinator with branches and no
     * user row would be invisible until something joined on it.
     */
    expect(DB::table('model_has_roles')->count())
        ->toBe(DB::table('model_has_roles')->whereIn('model_id', User::query()->pluck('id'))->count());

    expect(User::query()->where('email', 'c.new@mu.edu.sa')->exists())->toBeFalse();
});

test('the caller is told, rather than left to assume it worked', function (): void {
    aUser('SUPER_ADMIN');

    Schema::drop('audit_logs');

    /*
     * FR-030 — the failure surfaces. A swallowed exception here would be the worst outcome of the
     * three: the operator sees a success message, the account does not exist, and nothing in any
     * log says why.
     */
    $thrown = null;

    try {
        app(CreateUser::class)->execute([
            'name_ar' => 'أ', 'name_en' => 'A',
            'email' => 'silent@mu.edu.sa', 'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ]);
    } catch (Throwable $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
});
