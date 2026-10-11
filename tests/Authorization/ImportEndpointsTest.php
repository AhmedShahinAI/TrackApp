<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| T218 — allow and deny for every import endpoint (constitution VIII, contracts/imports.md)
|--------------------------------------------------------------------------
|
| All nine of the contract's endpoints. The ninth, POST /sections/{id}/imports, is the instructor's
| section upload (US6, T254); its scope case — a foreign section is a 404, audited — is in
| tests/Scope/InstructorCannotImportForeignSectionTest.php (T251).
|
| Two kinds of deny, because the policy has two halves:
|
|   - **No permission** — 403, on every endpoint.
|   - **Not the owner** — a second Super Admin, holding every permission there is, is still refused
|     every step that moves someone else's import forward. contracts/imports.md says "the caller must
|     own the job", and the reason is that `import.committed` names whoever confirmed the preview.
|     Reading the job stays open to them; the global scope, not ownership, decides visibility.
|
*/

/**
 * @param  list<string>  $permissions
 */
function anImportCaller(array $permissions): User
{
    $user = aUser('INSTRUCTOR');

    $role = Role::findOrCreate('IMPORT_PROBE_'.md5(implode(',', $permissions)), 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles([$role->name]);

    return $user->fresh();
}

/**
 * The world, a second Super Admin who owns nothing, and a caller with no import permission.
 *
 * @return array<string, mixed>
 */
function endpointWorld(): array
{
    $world = importWorld();
    $owner = $world['admin'];
    $otherAdmin = aUser('SUPER_ADMIN');
    $nobody = anImportCaller([]);

    return compact('world', 'owner', 'otherAdmin', 'nobody');
}

/**
 * A job owned by a caller with no import permission at all.
 *
 * Owned so the scope admits them: a central job names no section, so it is visible only to whoever
 * ran it, and a job the caller cannot see is a 404 from the binding before any permission is asked
 * — correct, and not what a deny-case test is for.
 *
 * @param  array<string, mixed>  $world
 */
function aJobOwnedBy(array $world, User $user, string $status): ImportJob
{
    return ImportJob::query()->create([
        'user_id' => $user->id,
        'mode' => 'central',
        'term_id' => $world['term']->id,
        'original_filename' => 'roster.xlsx',
        'file_path' => 'imports/roster.xlsx',
        'file_hash' => str_repeat('e', 64),
        'status' => $status,
    ]);
}

/**
 * A job of the owner's, advanced to the given state through the real endpoints.
 *
 * @param  array<string, mixed>  $world
 */
function ownedJobAt(array $world, string $state): ImportJob
{
    $job = uploadRoster($world['admin'], rosterFile([
        rosterRow($world), rosterRow($world, ['university_id' => 'bad']),
    ]), $world['term']->id);

    if ($state === 'uploaded') {
        return $job;
    }

    if ($state === 'mapped') {
        test()->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
            'mapping' => $job->file_metadata['suggested_mapping'],
        ])->assertOk();

        return $job->refresh();
    }

    $job = mapAndValidate($world['admin'], $job);

    if ($state === 'validated') {
        return $job;
    }

    $job = previewAndCommit($world['admin'], $job);

    if ($state === 'failed') {
        $job->forceFill(['status' => 'failed'])->save();
    }

    return $job->refresh();
}

test('api.v1.imports.index — allow and deny', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $this->actingAs($owner)->getJson(route('api.v1.imports.index'))->assertOk();
    $this->actingAs(anImportCaller(['import.view']))->getJson(route('api.v1.imports.index'))->assertOk();
    $this->actingAs($nobody)->getJson(route('api.v1.imports.index'))->assertForbidden();
});

test('api.v1.imports.store — allow, and deny without import.central.run', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $payload = fn () => ['file' => rosterFile([rosterRow($world)]), 'term_id' => $world['term']->id];

    $this->actingAs($owner)->post(route('api.v1.imports.store'), $payload(), ['Accept' => 'application/json'])->assertCreated();

    // An instructor holds import.view and import.section.run, and neither opens a central import.
    $this->actingAs($world['instructor'])->post(route('api.v1.imports.store'), $payload(), ['Accept' => 'application/json'])->assertForbidden();
    $this->actingAs($nobody)->post(route('api.v1.imports.store'), $payload(), ['Accept' => 'application/json'])->assertForbidden();
});

test('api.v1.imports.show — allow to the owner and to another administrator, deny without import.view', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'uploaded');

    $this->actingAs($owner)->getJson(route('api.v1.imports.show', $job))->assertOk();
    $this->actingAs($otherAdmin)->getJson(route('api.v1.imports.show', $job))->assertOk();
    $this->actingAs($nobody)->getJson(route('api.v1.imports.show', aJobOwnedBy($world, $nobody, 'uploaded')))->assertForbidden();
});

test('api.v1.imports.mapping.update — owner only', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'uploaded');
    $body = ['mapping' => $job->file_metadata['suggested_mapping']];

    $this->actingAs($nobody)->putJson(route('api.v1.imports.mapping.update', aJobOwnedBy($world, $nobody, 'uploaded')), $body)->assertForbidden();
    $this->actingAs($otherAdmin)->putJson(route('api.v1.imports.mapping.update', $job), $body)->assertForbidden();
    $this->actingAs($owner)->putJson(route('api.v1.imports.mapping.update', $job), $body)->assertOk();
});

test('api.v1.imports.validate — owner only', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'mapped');

    $this->actingAs($nobody)->postJson(route('api.v1.imports.validate', aJobOwnedBy($world, $nobody, 'mapped')))->assertForbidden();
    $this->actingAs($otherAdmin)->postJson(route('api.v1.imports.validate', $job))->assertForbidden();
    $this->actingAs($owner)->postJson(route('api.v1.imports.validate', $job))->assertAccepted();
});

test('api.v1.imports.preview — owner only', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'validated');

    $this->actingAs($nobody)->getJson(route('api.v1.imports.preview', aJobOwnedBy($world, $nobody, 'validated')))->assertForbidden();
    $this->actingAs($otherAdmin)->getJson(route('api.v1.imports.preview', $job))->assertForbidden();
    $this->actingAs($owner)->getJson(route('api.v1.imports.preview', $job))->assertOk();
});

test('api.v1.imports.commit — owner only', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'validated');
    $this->actingAs($owner)->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $this->actingAs($nobody)->postJson(route('api.v1.imports.commit', aJobOwnedBy($world, $nobody, 'validated')), ['confirm' => true])->assertForbidden();
    $this->actingAs($otherAdmin)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertForbidden();
    $this->actingAs($owner)->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
});

test('api.v1.imports.resume — owner only', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'failed');

    $this->actingAs($nobody)->postJson(route('api.v1.imports.resume', aJobOwnedBy($world, $nobody, 'failed')))->assertForbidden();
    $this->actingAs($otherAdmin)->postJson(route('api.v1.imports.resume', $job))->assertForbidden();
    $this->actingAs($owner)->postJson(route('api.v1.imports.resume', $job))->assertAccepted();
});

test('api.v1.imports.errors.download — allow with import.view, deny without', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'completed');

    $this->actingAs($owner)->get(route('api.v1.imports.errors.download', $job))->assertOk();
    $this->actingAs($otherAdmin)->get(route('api.v1.imports.errors.download', $job))->assertOk();
    $this->actingAs($nobody)->get(route('api.v1.imports.errors.download', aJobOwnedBy($world, $nobody, 'completed')))->assertForbidden();
});

test('the screens follow the same rules', function (): void {
    ['world' => $world, 'owner' => $owner, 'otherAdmin' => $otherAdmin, 'nobody' => $nobody] = endpointWorld();
    $job = ownedJobAt($world, 'uploaded');

    $this->actingAs($owner)->get(route('imports.index'))->assertOk();
    $this->actingAs($owner)->get(route('imports.create'))->assertOk();
    $this->actingAs($owner)->get(route('imports.show', $job))->assertOk()->assertSee(route('api.v1.imports.mapping.update', $job));

    // Another administrator may follow the job but is not offered its controls. A fresh session,
    // because AuthenticateSession rightly logs out a session whose stored password hash belongs to
    // someone else — which is what switching users mid-test looks like to it.
    $this->flushSession();
    $this->actingAs($otherAdmin)->get(route('imports.show', $job))->assertOk()->assertDontSee(route('api.v1.imports.mapping.update', $job));

    $this->flushSession();
    $this->actingAs($world['instructor'])->get(route('imports.create'))->assertForbidden();

    $this->flushSession();
    $this->actingAs($nobody)->get(route('imports.index'))->assertForbidden();
});

test('api.v1.sections.imports.store — allow to the section’s instructor, deny without import.section.run', function (): void {
    $world = sectionWorld();
    $file = fn () => ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)];

    $this->actingAs($world['instructor'])
        ->post(route('api.v1.sections.imports.store', $world['section']), $file(), ['Accept' => 'application/json'])
        ->assertCreated();

    // Teaches the section — so the scope admits it — but holds no import permission.
    $holder = anImportCaller(['section.view']);
    App\Models\Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['instructor_id' => $holder->id]);

    $this->actingAs($holder->fresh())
        ->post(route('api.v1.sections.imports.store', $world['section']), $file(), ['Accept' => 'application/json'])
        ->assertForbidden();
});
