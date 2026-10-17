<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T433 — a coordinator's queue and its totals hold only their branches' requests (FR-140)
|--------------------------------------------------------------------------
|
| Through the real endpoints, never by calling the scope directly: the guarantee is what a coordinator
| can reach, and the route is where they reach it.
|
*/

test('the queue, its rows and its total contain only the coordinator\'s branch', function (): void {
    $world = supportStaffWorld();
    $mine = [supportRequestFor($world['branch']), supportRequestFor($world['branch'])];
    $theirs = [supportRequestFor($world['other']), supportRequestFor($world['other']), supportRequestFor($world['other'])];

    $response = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertOk();

    expect($response->json('meta.total'))->toBe(2)
        ->and($response->collect('data')->pluck('reference')->sort()->values()->all())
        ->toBe(collect($mine)->pluck('display_reference')->sort()->values()->all());

    foreach ($theirs as $request) {
        expect((string) $response->getContent())->not->toContain($request->reference);
    }
});

test('every filter, aimed exactly at another branch\'s request, stays inside the scope', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);
    $theirs = supportRequestFor($world['other'], ['status' => 'in_progress', 'category' => 'other', 'assigned_to' => $world['outsider']->id]);

    // Each filter set to match the foreign request and nothing else of the coordinator's.
    $probes = [
        ['status' => 'in_progress'],
        ['category' => 'other'],
        ['assigned_to' => $world['outsider']->id],
        ['q' => $theirs->display_reference],
        ['q' => (string) $theirs->student?->university_id],
        ['branch_id' => $world['other']->id],
        ['status' => 'in_progress', 'category' => 'other', 'assigned_to' => $world['outsider']->id, 'branch_id' => $world['other']->id],
    ];

    foreach ($probes as $query) {
        $response = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests?'.http_build_query($query))->assertOk();

        expect((string) $response->getContent())->not->toContain($theirs->display_reference, json_encode($query) ?: '');
    }
});

test('searching for another branch\'s reference finds nothing', function (): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other']);

    $this->actingAs($world['coordinator'])
        ->getJson('/api/v1/support-requests?q='.$theirs->display_reference)
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

test('the Super Admin sees every branch', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);
    supportRequestFor($world['other']);

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->assertJsonPath('meta.total', 2);
});

test('the staff screens carry the per-user throttle, as the API does (Phase 23 review)', function (): void {
    foreach (['support-requests.index', 'support-requests.show'] as $name) {
        $route = Illuminate\Support\Facades\Route::getRoutes()->getByName($name);

        expect($route?->gatherMiddleware())->toContain('throttle:api');
    }
});

test('a route binding by another key still binds, through the scope', function (): void {
    $world = supportStaffWorld();
    $mine = supportRequestFor($world['branch']);
    $theirs = supportRequestFor($world['other']);

    $this->actingAs($world['coordinator']);

    expect((new App\Models\SupportRequest)->resolveRouteBinding((string) $mine->id, 'id')?->id)->toBe($mine->id)
        ->and((new App\Models\SupportRequest)->resolveRouteBinding((string) $theirs->id, 'id'))->toBeNull()
        ->and((new App\Models\SupportRequest)->resolveRouteBinding(strtolower($mine->display_reference))?->id)->toBe($mine->id);
});
