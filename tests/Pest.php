<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case bindings
|--------------------------------------------------------------------------
|
| F-02 fix. Without this file Pest never binds TestCase to the test directories, so `$this` in a
| test is a plain PHPUnit case with no Laravel application behind it. A test calling
| `$this->get('/up')` fails with an undefined method, which is how the gap was found: the
| placeholder tests passed only because `expect(1 + 1)->toBe(2)` needs no application.
|
| Every suite except Unit gets the application. Unit tests are deliberately left without it — a
| unit test that needs a booted framework is an integration test wearing the wrong label, and
| keeping the boundary visible here is cheaper than relitigating it in review.
|
*/

// Architecture reads files and route definitions, so it needs the application but not a database.
// A per-test transaction there would be pure overhead on the suite that runs most often.
pest()->extend(TestCase::class)->in('Architecture');

// The suites that touch data get RefreshDatabase. Scope and Authorization need real rows to prove a
// cross-branch leak cannot happen (constitution principle I); Performance needs them to measure
// anything at all.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Scope', 'Authorization', 'Performance');

// Unit is deliberately left unbound: a unit test that needs a booted framework is an integration
// test wearing the wrong label, and keeping that boundary visible here is cheaper than
// relitigating it in review.

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/**
 * Assert that two responses are indistinguishable — identical status, identical body, and no header
 * that varies with the reason.
 *
 * Exists for the enumeration suite the support channel requires (FR-120, FR-128, SC-026, SC-029):
 * three submission failures and two retrieval failures must be byte-identical, or roster membership
 * leaks one identifier at a time. A shared expectation keeps every one of those tests asserting the
 * same thing rather than each author deciding what "identical" means.
 */
expect()->extend('toBeIndistinguishableFrom', function (mixed $other) {
    expect($this->value->getStatusCode())->toBe($other->getStatusCode());
    expect($this->value->getContent())->toBe($other->getContent());

    foreach (['content-type', 'cache-control', 'x-reason'] as $header) {
        expect($this->value->headers->get($header))->toBe($other->headers->get($header));
    }

    return $this;
});

/*
|--------------------------------------------------------------------------
| Shared fixtures
|--------------------------------------------------------------------------
|
| Functions rather than a trait, because several US1 test files need the same two and declaring
| them in one test file only works while that file happens to load first. Here they are
| unambiguously available to every test.
|
*/

/**
 * The single college row. Created on demand, so a test that needs a user does not also have to know
 * that users belong to an organization.
 */
function anOrganisation(): App\Models\Organization
{
    return App\Models\Organization::query()->where('type', 'college')->first()
        ?? App\Models\Organization::factory()->create(['type' => 'college']);
}

/**
 * A user with a known password and a real role.
 *
 * Seeds permissions and roles first when they are absent: syncRoles() with a name that has no row
 * assigns nothing and fails silently, so a test asserting a permission would fail for the wrong
 * reason and the author would go looking in the wrong place.
 *
 * @param  array<string, mixed>  $attributes
 */
function aUser(string $role = 'INSTRUCTOR', array $attributes = []): App\Models\User
{
    if (! Spatie\Permission\Models\Role::query()->where('name', $role)->exists()) {
        (new Database\Seeders\PermissionSeeder)->run();
        (new Database\Seeders\RoleSeeder)->run();

        /*
         * Settings too, not just roles. The application treats its configuration as present — the
         * Settings facade throws on an unknown key by design, because a silent null would surface
         * as a validation rule that permits everything or a limit of zero. A test database without
         * the seeded settings is therefore a broken deployment, not a lighter fixture.
         */
        (new Database\Seeders\SettingSeeder)->run();
    }

    $user = new App\Models\User;

    $user->forceFill(array_merge([
        'organization_id' => anOrganisation()->id,
        'name_ar' => 'مستخدم تجريبي',
        'name_en' => 'Test User',
        'email' => 'user'.uniqid().'@mu.edu.sa',
        'password' => 'Track2026',
        'locale' => 'ar',
        'is_active' => true,
        'first_login_at' => now()->subDay(),
        'last_login_at' => now()->subDay(),
    ], $attributes))->saveQuietly();

    $user->syncRoles([$role]);

    return $user->refresh();
}

/**
 * The first error message for a field, however Laravel happened to flash it.
 *
 * `session('errors')` is a ViewErrorBag after a redirect but a plain array in some contexts, and a
 * test that assumes one shape fails for a reason that has nothing to do with what it is testing.
 */
function firstError(string $field): ?string
{
    $errors = session('errors');

    if ($errors instanceof Illuminate\Support\ViewErrorBag) {
        return $errors->getBag('default')->first($field) ?: null;
    }

    if (is_array($errors)) {
        $messages = $errors[$field] ?? null;

        return is_array($messages) ? ($messages[0] ?? null) : $messages;
    }

    return null;
}

/**
 * The 43 action keys contracts/audit.md defines, in the order it lists them (38, plus Amendment 2's five).
 *
 * Transcribed rather than parsed out of the markdown table: a parser that drifts turns a contract
 * test into a test of the parser, and a transcription that drifts is caught the moment someone
 * compares the two — which is the whole job of the tests that consume this.
 *
 * C-11 settled the naming: where the design kit and the specification disagree
 * (`auth.signed_in`/`auth.login`, `term.current_set`/`term.set_current`,
 * `user.role_changed`/`user.roles_changed`, `import.completed`/`import.committed`), the
 * specification's key is the identifier and the kit's string is only its label.
 *
 * @return list<string>
 */
function contractActionKeys(): array
{
    return [
        'auth.login', 'auth.logout', 'auth.login_failed', 'auth.login_blocked',

        'user.created', 'user.updated', 'user.deactivated', 'user.password_changed',
        'user.password_reset', 'user.roles_changed', 'user.branches_changed',

        'access.denied', 'section.access_granted',

        'term.created', 'term.updated', 'term.status_changed', 'term.set_current',

        'branch.created', 'branch.updated', 'branch.deactivated',
        'course.created', 'course.updated',

        'program.created', 'program.updated', 'program.deactivated', 'program.courses_changed',

        'section.created', 'section.updated', 'section.instructor_changed',

        'student.created', 'student.updated', 'enrollment.created', 'enrollment.withdrawn',

        'import.uploaded', 'import.committed', 'import.resumed', 'import.error_report_downloaded',

        'audit.exported',

        // Amendment 2 (data-model.md Group 7 "Audit actions"), added with the model that emits them.
        'support.request_created', 'support.responded', 'support.status_changed',
        'support.reassigned', 'support.anonymised',
    ];
}

/**
 * The contract actions no implemented flow can emit yet, and the phase that will emit each.
 *
 * ActionCoverageTest performs every *other* key once and asserts exactly one entry. These cannot be
 * performed — their features do not exist — so listing them here is how the test stays honest about
 * its own coverage instead of quietly asserting 25 while the task says 38.
 *
 * Each line is removed when its phase lands, and the test's reach grows by itself.
 *
 * @return array<string, string>
 */
function actionsAwaitingTheirPhase(): array
{
    return [
        'term.created' => 'Phase 7 — US4, academic structure',
        'term.updated' => 'Phase 7 — US4',
        'term.status_changed' => 'Phase 7 — US4',
        'term.set_current' => 'Phase 7 — US4',
        'branch.deactivated' => 'Phase 7 — US4',
        'section.instructor_changed' => 'Phase 7 — US4',
        'enrollment.withdrawn' => 'Phase 9 — US6, instructor rosters',

        // Amendment 2. SupportRequest exists from Phase 19; nothing creates or changes one until these.
        'support.request_created' => 'Phase 20 — US9, the public submission',
        'support.responded' => 'Phase 21 — US10, staff answer requests',
        'support.status_changed' => 'Phase 21 — US10',
        'support.reassigned' => 'Phase 21 — US10',
        'support.anonymised' => 'Phase 25 — the retention sweep',
    ];
}

/*
|--------------------------------------------------------------------------
| Import fixtures
|--------------------------------------------------------------------------
|
| US5's world and six-step walk, kept in their own file because they are a page long and only the
| import suites need them. Loaded here so they are as available as everything above.
|
*/

require_once __DIR__.'/Support/Imports.php';

// Phase 15 — the registrar import's world, files and walk.
require_once __DIR__.'/Support/Registrar.php';

// Phase 16 — course and staff files, and their walk.
require_once __DIR__.'/Support/BulkImports.php';

// US7's screen registry, shared by the localisation and UX sweeps.
require_once __DIR__.'/Support/Screens.php';

// Phase 12's typed EAV capacity spike: its database and the five measured queries.
require_once __DIR__.'/Support/EavSpike.php';

// Phase 14's programme fixtures, shared by the Programs, Sections and Authorization suites.
require_once __DIR__.'/Support/Programs.php';
