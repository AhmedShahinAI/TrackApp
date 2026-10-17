<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T404 — no rejection without a reason, and the reason is the student's to read (FR-149)
|--------------------------------------------------------------------------
*/

test('a rejection without a reason is refused and the request is unchanged', function (?string $reason): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['coordinator'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'rejected', 'reason' => $reason])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');

    expect($request->fresh()?->status->value)->toBe('in_progress')
        ->and(supportAuditActions($request))->toBe([]);
})->with(['missing' => [null], 'blank' => ['   ']]);

test('the reason is stored on the request, audited, and in what the student will be shown', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['coordinator'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'rejected', 'reason' => 'Your timetable is set by the registrar.'])
        ->assertOk()
        ->assertJsonPath('data.rejection_reason', 'Your timetable is set by the registrar.');

    $entry = Illuminate\Support\Facades\DB::table('audit_logs')->where('entity_id', $request->id)->where('action', 'support.status_changed')->sole();

    expect($request->fresh()?->rejection_reason)->toBe('Your timetable is set by the registrar.')
        ->and(json_decode((string) $entry->new_values, true))->toBe(['status' => 'rejected', 'rejection_reason' => 'Your timetable is set by the registrar.']);
});

test('the screen asks for the reason in a danger confirmation and reopens it when one is missing', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    $page = route('support-requests.show', $request);

    $this->actingAs($world['admin'])->get($page)
        ->assertSee('id="support-reject"', false)
        ->assertSee(__('support.staff.reject.impact'));

    $this->actingAs($world['admin'])->from($page)
        ->patch('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'rejected', 'reason' => '', '_dialog' => 'support-reject'])
        ->assertRedirect($page)
        ->assertSessionHasErrors('reason');
});
