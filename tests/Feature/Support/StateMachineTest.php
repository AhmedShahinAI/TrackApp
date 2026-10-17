<?php

declare(strict_types=1);

use App\Models\SupportResponse;
use App\Modules\Support\Actions\TransitionSupportRequest;

/*
|--------------------------------------------------------------------------
| T403 — every permitted transition succeeds and every other is refused (FR-148)
|--------------------------------------------------------------------------
|
| All 25 pairs of (from, to), driven through PATCH …/status as the Super Admin — who may do everything
| the machine allows — so a refusal here is the machine's, never a permission's.
|
| The expected outcome is the table below, transcribed by hand from data-model.md "State machine" —
| NOT read from TransitionSupportRequest::TRANSITIONS (Phase 21 review). A test that asked the constant
| what to expect would pass whatever the constant said; this one fails the day the two disagree.
|
*/

const SUPPORT_STATES = ['new', 'in_progress', 'resolved', 'closed', 'rejected'];

/**
 * data-model.md, row by row: from → [every target, permitted or not].
 *
 *     new          → in_progress ✓   rejected ✓
 *     in_progress  → resolved ✓      rejected ✓
 *     resolved     → closed ✓        in_progress ✓ (reopening)
 *     closed       → nothing
 *     rejected     → nothing
 */
const SUPPORT_TRANSITION_TABLE = [
    'new' => ['new' => false, 'in_progress' => true, 'resolved' => false, 'closed' => false, 'rejected' => true],
    'in_progress' => ['new' => false, 'in_progress' => false, 'resolved' => true, 'closed' => false, 'rejected' => true],
    'resolved' => ['new' => false, 'in_progress' => true, 'resolved' => false, 'closed' => true, 'rejected' => false],
    'closed' => ['new' => false, 'in_progress' => false, 'resolved' => false, 'closed' => false, 'rejected' => false],
    'rejected' => ['new' => false, 'in_progress' => false, 'resolved' => false, 'closed' => false, 'rejected' => false],
];

/** @return array<string, array{string, string, bool}> */
function everyTransition(): array
{
    $pairs = [];

    foreach (SUPPORT_TRANSITION_TABLE as $from => $targets) {
        foreach ($targets as $to => $permitted) {
            $pairs["$from → $to"] = [$from, $to, $permitted];
        }
    }

    return $pairs;
}

test('a transition succeeds exactly when the state machine permits it', function (string $from, string $to, bool $permitted): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => $from, 'rejection_reason' => $from === 'rejected' ? 'Out of scope' : null]);

    // `resolved` needs a reply on record; give every request one so that rule is not what is tested.
    $reply = new SupportResponse(['body' => 'Reply']);
    $reply->forceFill(['support_request_id' => $request->id, 'user_id' => $world['admin']->id])->save();

    $response = $this->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/status', [
        'status' => $to,
        'reason' => $to === 'rejected' ? 'Not something the platform can change' : null,
    ]);

    if ($permitted) {
        $response->assertOk()->assertJsonPath('data.status.value', $to);
        expect($request->fresh()?->status->value)->toBe($to);
    } else {
        $response->assertStatus(409);
        expect($request->fresh()?->status->value)->toBe($from);
    }
})->with(everyTransition());

test('the constant agrees with the table, cell for cell', function (): void {
    foreach (SUPPORT_TRANSITION_TABLE as $from => $targets) {
        foreach ($targets as $to => $permitted) {
            expect(in_array($to, TransitionSupportRequest::TRANSITIONS[$from], true))->toBe($permitted, "$from → $to");
        }
    }
});

test('the refusals the spec names are refusals', function (string $from, string $to): void {
    expect(in_array($to, TransitionSupportRequest::TRANSITIONS[$from], true))->toBeFalse();
})->with([
    'new → closed' => ['new', 'closed'],
    'new → resolved' => ['new', 'resolved'],
    'closed → in_progress' => ['closed', 'in_progress'],
    'closed → resolved' => ['closed', 'resolved'],
    'rejected → in_progress' => ['rejected', 'in_progress'],
]);

test('resolving needs a reply to the student first', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'resolved'])
        ->assertStatus(409);
});

test('a coordinator may not close: closing needs support.close (403)', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'resolved']);

    $this->actingAs($world['coordinator'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'closed'])
        ->assertForbidden();

    expect($request->fresh()?->status->value)->toBe('resolved');
});

test('every change is one support.status_changed with old and new', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'resolved']);

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'in_progress'])
        ->assertOk();

    $entry = Illuminate\Support\Facades\DB::table('audit_logs')->where('entity_id', $request->id)->where('entity_type', 'SupportRequest')->sole();

    expect($entry->action)->toBe('support.status_changed')
        ->and(json_decode((string) $entry->old_values, true))->toBe(['status' => 'resolved'])
        ->and(json_decode((string) $entry->new_values, true))->toBe(['status' => 'in_progress']);
});

test('the state history the detail returns is read from those entries', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'resolved']);

    $this->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'in_progress']);

    $history = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->collect('data.history');

    expect($history->pluck('status')->last())->toBe('in_progress')
        ->and($history->last()['by']['id'] ?? null)->toBe($world['admin']->id);
});

test('a reason given with a reopen is kept in the entry, and the history shows it', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'resolved']);

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'in_progress', 'reason' => 'The fix did not hold'])
        ->assertOk();

    $entry = Illuminate\Support\Facades\DB::table('audit_logs')->where('entity_id', $request->id)->where('action', 'support.status_changed')->sole();

    expect(json_decode((string) $entry->new_values, true))->toBe(['status' => 'in_progress', 'reason' => 'The fix did not hold'])
        ->and($request->fresh()?->rejection_reason)->toBeNull()
        ->and($this->getJson('/api/v1/support-requests/'.$request->reference)->collect('data.history')->last()['reason'] ?? null)->toBe('The fix did not hold');
});
