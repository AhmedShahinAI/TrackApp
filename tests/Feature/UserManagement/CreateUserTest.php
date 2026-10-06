<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| T108 — creating an account (FR-003, FR-004, FR-005, FR-007)
|--------------------------------------------------------------------------
*/

function aBranchRow(): Branch
{
    return Branch::factory()->create(['organization_id' => anOrganisation()->id]);
}

test('a Super Admin creates an instructor with both names and a role', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'د. فيصل العتيبي',
            'name_en' => 'Dr. Faisal Al-Otaibi',
            'email' => 'f.alotaibi@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name_ar', 'د. فيصل العتيبي')
        ->assertJsonPath('data.roles', ['INSTRUCTOR'])
        ->assertJsonPath('data.is_active', true)
        // FR-003 — null on creation, which is what makes the recommendation appear exactly once.
        ->assertJsonPath('data.first_login_at', null);

    $created = User::query()->where('email', 'f.alotaibi@mu.edu.sa')->firstOrFail();

    /*
     * FR-007 — stored only as an irreversible hash.
     *
     * The algorithm is asserted on the configuration, not on the stored value: phpunit.xml
     * deliberately overrides HASH_DRIVER to bcrypt with 4 rounds so the suite does not pay 32 ms
     * of Argon2id per fixture user. Asserting the stored algorithm would therefore assert the test
     * override rather than what ships.
     */
    expect(Hash::check('Track2026', $created->password))->toBeTrue()
        ->and($created->password)->not->toBe('Track2026');

    // What ships, read from the shipped configuration rather than from the running one: config()
    // resolves HASH_DRIVER, which phpunit.xml has already overridden to bcrypt.
    expect((string) file_get_contents(base_path('.env.example')))->toContain('HASH_DRIVER=argon2id');
});

test('the response never contains a password field', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $response = $this->actingAs($admin)->postJson('/api/v1/users', [
        'name_ar' => 'اسم',
        'name_en' => 'Name',
        'email' => 'new@mu.edu.sa',
        'password' => 'Track2026',
        'role' => 'INSTRUCTOR',
    ]);

    // Asserted on the raw body rather than on a key, so a renamed field is caught too.
    $body = $response->getContent();

    expect($body)->not->toContain('password')
        ->and($body)->not->toContain('Track2026')
        ->and($body)->not->toContain('argon2');
});

test('the audit payload excludes the password entirely', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name_ar' => 'اسم',
        'name_en' => 'Name',
        'email' => 'audited@mu.edu.sa',
        'password' => 'Track2026',
        'role' => 'INSTRUCTOR',
    ]);

    $entry = DB::table('audit_logs')
        ->where('action', 'user.created')
        ->orderByDesc('id')
        ->first();

    expect($entry)->not->toBeNull();

    $payload = ($entry->old_values ?? '').($entry->new_values ?? '');

    // The allowlist in User::auditedAttributes() is what guarantees this, not the action
    // remembering to strip it — so this would fail if `password` were ever added to the list.
    expect($payload)->not->toContain('password')
        ->and($payload)->not->toContain('Track2026')
        ->and($payload)->not->toContain('argon2')
        // The non-sensitive attributes are there, so the entry is not simply empty.
        ->and($payload)->toContain('audited@mu.edu.sa');
});

test('a coordinator requires at least one branch', function (): void {
    $admin = aUser('SUPER_ADMIN');

    // FR-004.
    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'منسق',
            'name_en' => 'Coordinator',
            'email' => 'coord@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'BRANCH_COORDINATOR',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('branch_ids');

    expect(User::query()->where('email', 'coord@mu.edu.sa')->exists())->toBeFalse();
});

test('a coordinator created with branches gets them attached', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $branch = aBranchRow();

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'منسق',
            'name_en' => 'Coordinator',
            'email' => 'coord@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'BRANCH_COORDINATOR',
            'branch_ids' => [$branch->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.branches.0.id', $branch->id);

    $created = User::query()->where('email', 'coord@mu.edu.sa')->firstOrFail();

    expect($created->coordinatorBranchIds())->toBe([$branch->id]);
});

test('an instructor needs no branches at creation', function (): void {
    $admin = aUser('SUPER_ADMIN');

    // FR-004's other half: section links are optional at creation and establishable later.
    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'مدرس',
            'name_en' => 'Instructor',
            'email' => 'inst@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertCreated();
});

test('a duplicate email is refused', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aUser(attributes: ['email' => 'taken@mu.edu.sa']);

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'email' => 'taken@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('a duplicate email is refused even against a soft-deleted account', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $gone = aUser(attributes: ['email' => 'departed@mu.edu.sa']);
    $gone->delete();

    // find(), not fresh(): Model::fresh() queries WITHOUT scopes, so it returns a soft-deleted row
    // and would make this assertion look wrong when the soft delete had in fact worked.
    expect(User::query()->find($gone->id))->toBeNull()
        ->and(User::withTrashed()->find($gone->id))->not->toBeNull();

    /*
     * FR-005. Reusing a soft-deleted person's address would silently transfer their audit trail to
     * whoever got the address next — every entry naming that user id stays, and the new holder
     * inherits it. The unique index is on `email` alone for exactly this reason.
     */
    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'email' => 'departed@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('a duplicate email is refused regardless of capitalisation', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aUser(attributes: ['email' => 'taken@mu.edu.sa']);

    // Normalised before validating, so the check compares what would be stored. On a
    // case-sensitive collation — which the test suite runs on — the naive version lets this through.
    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'email' => 'TAKEN@MU.EDU.SA',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('a password failing the policy is refused', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'email' => 'weak@mu.edu.sa',
            'password' => 'short',
            'role' => 'INSTRUCTOR',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('the locale defaults from the setting', function (): void {
    $admin = aUser('SUPER_ADMIN');

    (new Database\Seeders\SettingSeeder)->run();

    $this->actingAs($admin)->postJson('/api/v1/users', [
        'name_ar' => 'اسم',
        'name_en' => 'Name',
        'email' => 'defaulted@mu.edu.sa',
        'password' => 'Track2026',
        'role' => 'INSTRUCTOR',
    ])->assertCreated()->assertJsonPath('data.locale', 'ar');
});

test('a caller without user.create is refused', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $this->actingAs($instructor)
        ->postJson('/api/v1/users', [
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'email' => 'nope@mu.edu.sa',
            'password' => 'Track2026',
            'role' => 'INSTRUCTOR',
        ])
        ->assertForbidden();
});
