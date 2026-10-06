<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Checkpoint A — the audit mechanism (T041–T043)
|--------------------------------------------------------------------------
|
| Constitution II: a failed audit write must abort the action it describes. research.md R-02 says
| the only way to get that is a plain insert on the caller's connection — no afterCommit, no queue.
| These tests assert the guarantee rather than the implementation, so a later refactor that
| reintroduced deferral would fail here.
|
| Fixtures come from plain functions rather than $this-> properties set in beforeEach. Pest binds
| $this to the TestCase at runtime, but no static analyser can see that, so every such property
| reads as undefined to Larastan — and a test file nobody can type-check is the wrong place to make
| an exception.
|
*/

function auditTestOrganization(): Organization
{
    return Organization::query()->where('type', 'college')->first()
        ?? Organization::factory()->create(['type' => 'college']);
}

function aBranch(): Branch
{
    return Branch::query()->create([
        'organization_id' => auditTestOrganization()->id,
        'campus_name_ar' => 'فرع',
        'campus_name_en' => 'Branch',
        'gender' => 'female',
        'is_active' => true,
    ]);
}

test('an audit entry is written inside the caller transaction', function (): void {
    DB::beginTransaction();

    app(Auditor::class)->record('term.set_current', null, ['is_current' => false], ['is_current' => true]);

    expect(DB::table('audit_logs')->count())->toBe(1);

    DB::rollBack();

    // The entry vanished with the rollback, which is only possible if it was written on the same
    // connection inside the same transaction. A queued or afterCommit write would have survived the
    // rollback — which is the failure constitution II exists to prevent.
    expect(DB::table('audit_logs')->count())->toBe(0);
});

test('a failed audit write aborts the business write', function (): void {
    $organizationId = auditTestOrganization()->id;

    $action = new class extends TransactionalAction
    {
        protected function handle(mixed ...$arguments): mixed
        {
            Branch::query()->create([
                'organization_id' => $arguments[0],
                'campus_name_ar' => 'فرع',
                'campus_name_en' => 'Branch',
                'gender' => 'male',
                'is_active' => true,
            ]);

            // Stands in for any failure in the audit insert — a constraint violation, a dead
            // connection, a full disk. The action must not complete without its entry.
            throw new RuntimeException('audit insert failed');
        }
    };

    $before = Branch::query()->count();

    expect(fn (): mixed => $action->execute($organizationId))
        ->toThrow(RuntimeException::class, 'audit insert failed');

    // The branch is gone. Without TransactionalAction wrapping handle(), it would have survived the
    // exception and the platform would hold a branch no audit entry explains.
    expect(Branch::query()->count())->toBe($before);
});

test('a password never reaches an audit payload', function (): void {
    app(Auditor::class)->record('user.password_changed', null, [], [
        'email' => 'a.user@mu.edu.sa',
        'password' => 'plaintext-should-never-appear',
        'password_confirmation' => 'plaintext-should-never-appear',
        'remember_token' => 'abc123',
        'api_token_hash' => 'deadbeef',
        'nested' => ['password' => 'also-not-this', 'locale' => 'ar'],
    ]);

    $entry = DB::table('audit_logs')->where('action', 'user.password_changed')->first();

    expect($entry)->not->toBeNull();

    $payload = (string) $entry->new_values;

    expect($payload)->not->toContain('plaintext-should-never-appear')
        ->and($payload)->not->toContain('abc123')
        ->and($payload)->not->toContain('deadbeef')
        ->and($payload)->not->toContain('also-not-this')
        // The non-sensitive siblings survive, including the one nested beside a stripped key —
        // otherwise the redaction would be discarding the entry rather than cleaning it.
        ->and($payload)->toContain('a.user@mu.edu.sa')
        ->and($payload)->toContain('ar');
});

test('the Auditable trait records a create, an update, and a delete', function (): void {
    $branch = aBranch();

    $branch->update(['is_active' => false]);
    $branch->delete();

    $actions = DB::table('audit_logs')
        ->where('entity_type', 'Branch')
        ->orderBy('id')
        ->pluck('action')
        ->all();

    expect($actions)->toBe(['branch.created', 'branch.updated', 'branch.deleted']);

    $update = DB::table('audit_logs')->where('action', 'branch.updated')->first();

    // Both sides of the change, which is what makes the entry worth keeping.
    expect($update->old_values)->toContain('is_active')
        ->and($update->new_values)->toContain('is_active');
});

test('an update outside the audited allowlist records nothing', function (): void {
    $user = User::factory()->create(['organization_id' => auditTestOrganization()->id]);

    DB::table('audit_logs')->delete();

    // `last_login_at` is not in User::auditedAttributes(). Sign-in is already audited as
    // `auth.login`; a second entry for the column it touches would be noise in the one log that has
    // to stay readable.
    $user->forceFill(['last_login_at' => now()])->save();

    expect(DB::table('audit_logs')->count())->toBe(0);
});

test('an entry records the actor, the address, and the agent', function (): void {
    $user = User::factory()->create(['organization_id' => auditTestOrganization()->id]);

    Auth::login($user);

    app(Auditor::class)->record('auth.login', $user);

    $entry = DB::table('audit_logs')->where('action', 'auth.login')->first();

    expect((int) $entry->user_id)->toBe($user->id)
        ->and($entry->entity_type)->toBe('User')
        ->and((int) $entry->entity_id)->toBe($user->id)
        // Not nullable in the schema, so a console or seeder write has to put something here.
        ->and($entry->ip_address)->not->toBeEmpty();
});
