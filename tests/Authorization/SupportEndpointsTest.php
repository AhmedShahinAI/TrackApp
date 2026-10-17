<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T408 — an allow case and a deny case for each of the five staff endpoints
|--------------------------------------------------------------------------
|
| contracts/support-staff.md. Plus, for every endpoint, the two refusals the contract adds: an
| instructor is refused (FR-142), and a request outside the caller's branches is a 404 — not a 403 —
| recorded as access.denied (FR-143, FR-158).
|
*/

/**
 * Each endpoint by its route name (EveryRouteAuthorizedTest matches routes to this suite by name):
 * api.v1.support-requests.index, api.v1.support-requests.show, api.v1.support-requests.responses.store,
 * api.v1.support-requests.status, api.v1.support-requests.assignee.
 *
 * @return array<string, array{string, string, Closure(): array<string, mixed>}>
 */
function supportEndpoints(): array
{
    return [
        'queue' => ['GET', '/api/v1/support-requests', fn (): array => []],
        'detail' => ['GET', '/api/v1/support-requests/{ref}', fn (): array => []],
        'respond' => ['POST', '/api/v1/support-requests/{ref}/responses', fn (): array => ['body' => 'Reply']],
        'status' => ['PATCH', '/api/v1/support-requests/{ref}/status', fn (): array => ['status' => 'rejected', 'reason' => 'Out of scope']],
        'assignee' => ['PATCH', '/api/v1/support-requests/{ref}/assignee', fn (): array => ['assigned_to' => null]],
    ];
}

/**
 * The role allowed each endpoint, and one refused it.
 *
 * @return array<string, array{string, string}>
 */
function supportEndpointRoles(): array
{
    return [
        'queue' => ['coordinator', 'instructor'],
        'detail' => ['coordinator', 'instructor'],
        'respond' => ['coordinator', 'instructor'],
        'status' => ['coordinator', 'instructor'],
        'assignee' => ['admin', 'coordinator'],
    ];
}

test('allowed', function (string $endpoint): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    [$method, $uri, $payload] = supportEndpoints()[$endpoint];
    [$allowed] = supportEndpointRoles()[$endpoint];

    $response = $this->actingAs($world[$allowed])->json($method, str_replace('{ref}', $request->reference, $uri), $payload());

    expect($response->status())->toBeIn([200, 201]);
})->with(array_keys(supportEndpoints()));

test('denied', function (string $endpoint): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    [$method, $uri, $payload] = supportEndpoints()[$endpoint];
    [, $denied] = supportEndpointRoles()[$endpoint];

    $response = $this->actingAs($world[$denied])->json($method, str_replace('{ref}', $request->reference, $uri), $payload());

    /*
     * An instructor holds no support permission (FR-142) AND reaches no request (the section
     * constraint on SupportRequest resolves to nothing). Where a request is bound, the scope answers
     * first — a 404, by the platform's 404-not-403 rule, so an instructor is not even told the
     * reference exists. Where none is (the queue), the permission does — a 403. Either is a refusal,
     * and nothing changes.
     */
    // An instructor gets 404 everywhere (Phase 23, SC-038): from the scope on a bound request, and from
    // the policy's denyAsNotFound on the queue. A coordinator refused assignment gets 403.
    expect($response->status())->toBe($denied === 'instructor' ? 404 : 403)
        ->and($request->fresh()?->status->value)->toBe('in_progress')
        ->and($request->fresh()?->assigned_to)->toBeNull();
})->with(array_keys(supportEndpoints()));

test('signed out is refused', function (string $endpoint): void {
    (new SettingSeeder)->run();
    [$method, $uri, $payload] = supportEndpoints()[$endpoint];

    $this->json($method, str_replace('{ref}', 'K7Q2M9XVT3X', $uri), $payload())->assertUnauthorized();
})->with(array_keys(supportEndpoints()));

test('another branch\'s request is a 404, indistinguishable from none, and recorded', function (string $endpoint): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other'], ['status' => 'in_progress']);
    [$method, $uri, $payload] = supportEndpoints()[$endpoint];
    $as = 'coordinator';

    // Assignment is the Super Admin's; to test scope rather than permission, grant it for this probe.
    if ($endpoint === 'assignee') {
        $world['coordinator']->givePermissionTo('support.assign');
    }

    DB::table('audit_logs')->delete();

    $outOfScope = $this->actingAs($world[$as])->json($method, str_replace('{ref}', $theirs->reference, $uri), $payload());
    $none = $this->actingAs($world[$as])->json($method, str_replace('{ref}', 'K7Q2M9XVT3X', $uri), $payload());

    $outOfScope->assertNotFound();
    $none->assertNotFound();

    expect($outOfScope->getContent())->toBe($none->getContent())
        ->and(DB::table('audit_logs')->where('action', 'access.denied')->count())->toBe(2)
        ->and($theirs->fresh()?->status->value)->toBe('in_progress');
    // The queue takes no reference; its scoping is QueueTest's.
})->with(array_values(array_diff(array_keys(supportEndpoints()), ['queue'])));

// One user per test: AuthenticateSession (session.auth) pins the first user's password hash to the
// session, so switching users mid-test signs the second one out — a harness artifact, not a refusal.
test('the staff screens are allowed to a coordinator', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['coordinator'])->get('/support-requests')->assertOk();
    $this->get('/support-requests/'.$request->reference)->assertOk();
});

test('the staff screens do not exist for an instructor', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['instructor'])->get('/support-requests')->assertNotFound();
    $this->get('/support-requests/'.$request->reference)->assertNotFound();
});
