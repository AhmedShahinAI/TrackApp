<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T407 — respond, transition and reassign each write exactly one entry with their own key
|--------------------------------------------------------------------------
|
| The keys are contracts/audit.md's (support.responded, support.status_changed, support.reassigned),
| and the model writes nothing of its own for an update (SupportRequest::bootAuditable), so a flow
| that also saved through Eloquent cannot double an entry or invent a `support.updated`.
|
*/

test('each staff action writes one entry, keyed by the contract, with the actor', function (string $action, Closure $perform, string $key): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $perform($this, $world, $request);

    $entries = Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'SupportRequest')->where('entity_id', $request->id)->get();

    expect($entries->pluck('action')->all())->toBe([$key])
        ->and((int) $entries[0]->user_id)->toBe($world['admin']->id);
})->with([
    'respond' => ['respond', function ($test, array $world, $request): void {
        $test->actingAs($world['admin'])->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'Done'])->assertCreated();
    }, 'support.responded'],
    'transition' => ['transition', function ($test, array $world, $request): void {
        $test->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'rejected', 'reason' => 'Out of scope'])->assertOk();
    }, 'support.status_changed'],
    'reassign' => ['reassign', function ($test, array $world, $request): void {
        $test->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => $world['coordinator']->id])->assertOk();
    }, 'support.reassigned'],
]);

test('the response entry names the response, not its words', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['admin'])->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'A private explanation'])->assertCreated();

    $entry = Illuminate\Support\Facades\DB::table('audit_logs')->where('action', 'support.responded')->sole();

    expect((string) $entry->new_values)->not->toContain('A private explanation')
        ->and(json_decode((string) $entry->new_values, true))->toHaveKey('response_id');
});
