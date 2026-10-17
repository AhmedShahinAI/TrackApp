<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T434 — another branch's reference is a 404, never a 403, and the attempt is recorded (FR-143, FR-158)
|--------------------------------------------------------------------------
|
| Through the API route and the Blade route alike, and identical to a reference that does not exist at
| all — a 403 would confirm the request is there.
|
*/

test('another branch\'s reference is a 404, the same as no such reference, and is recorded', function (string $route): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other']);
    $missing = 'K7Q2M9XVT3'.App\Modules\Support\Services\ReferenceGenerator::checkCharacter('K7Q2M9XVT3');
    DB::table('audit_logs')->delete();

    $outOfScope = $this->actingAs($world['coordinator'])->get(sprintf($route, $theirs->reference));
    $nonexistent = $this->actingAs($world['coordinator'])->get(sprintf($route, $missing));

    $outOfScope->assertNotFound();
    $nonexistent->assertNotFound();

    expect($outOfScope->getContent())->toBe($nonexistent->getContent())
        ->and(DB::table('audit_logs')->where('action', 'access.denied')->count())->toBe(2);

    // The record names the attempt, not the request: it must not confirm what it refused.
    $entry = DB::table('audit_logs')->where('action', 'access.denied')->first();
    expect(json_decode((string) $entry->new_values, true)['status'])->toBe('404');
})->with([
    'the API' => ['/api/v1/support-requests/%s'],
    'the screen' => ['/support-requests/%s'],
]);

test('acting on another branch\'s request by reference is a 404 too, and changes nothing', function (string $method, string $suffix, array $payload): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other'], ['status' => 'in_progress']);

    $this->actingAs($world['coordinator'])
        ->json($method, '/api/v1/support-requests/'.$theirs->reference.$suffix, $payload)
        ->assertNotFound();

    expect($theirs->fresh()?->status->value)->toBe('in_progress')
        ->and(App\Models\SupportResponse::query()->count())->toBe(0);
})->with([
    'respond' => ['POST', '/responses', ['body' => 'Reply']],
    'transition' => ['PATCH', '/status', ['status' => 'rejected', 'reason' => 'Out of scope']],
    'close' => ['PATCH', '/status', ['status' => 'closed']],
]);

test('reassigning another branch\'s request is a 404 even for someone allowed to reassign, and is recorded', function (): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other'], ['status' => 'in_progress']);

    // The permission granted, so a refusal can only be the scope's — a 403 here would confirm the request.
    $world['coordinator']->givePermissionTo('support.assign');
    DB::table('audit_logs')->delete();

    $this->actingAs($world['coordinator'])
        ->patchJson('/api/v1/support-requests/'.$theirs->reference.'/assignee', ['assigned_to' => $world['coordinator']->id])
        ->assertNotFound();

    expect($theirs->fresh()?->assigned_to)->toBeNull()
        ->and(DB::table('audit_logs')->where('action', 'access.denied')->count())->toBe(1);
});

test('any spelling of another branch\'s reference is the same 404 bytes', function (): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other']);
    $missing = 'K7Q2M9XVT3'.App\Modules\Support\Services\ReferenceGenerator::checkCharacter('K7Q2M9XVT3');

    $baseline = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests/'.$missing)->getContent();

    foreach ([$theirs->reference, $theirs->display_reference, strtolower($theirs->display_reference), strtolower($theirs->reference)] as $spelling) {
        $response = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests/'.$spelling);

        $response->assertNotFound();
        expect($response->getContent())->toBe($baseline, $spelling);
    }
});

test('a soft-deleted student\'s request is gone for the coordinator too (Phase 23 review)', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    App\Models\Student::query()->withoutGlobalScopes()->whereKey($request->student_id)->firstOrFail()->delete();

    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests/'.$request->reference)->assertNotFound();
    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertJsonPath('meta.total', 0);
});

test('opening another branch\'s new request does not move it', function (): void {
    $world = supportStaffWorld();
    $theirs = supportRequestFor($world['other']);

    $this->actingAs($world['coordinator'])->get('/support-requests/'.$theirs->reference)->assertNotFound();

    expect($theirs->fresh()?->status->value)->toBe('new');
});
