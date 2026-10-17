<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T401 — opening a new request moves it to in_progress and records who (FR-147)
|--------------------------------------------------------------------------
*/

test('opening a new request makes it in progress, audited once, with the opener as actor', function (bool $json): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $this->actingAs($world['coordinator']);
    $json
        ? $this->getJson('/api/v1/support-requests/'.$request->display_reference)->assertOk()->assertJsonPath('data.status.value', 'in_progress')
        : $this->get('/support-requests/'.$request->display_reference)->assertOk();

    $entry = DB::table('audit_logs')->where('entity_type', 'SupportRequest')->where('entity_id', $request->id)->where('action', 'support.status_changed')->sole();

    expect($request->fresh()?->status->value)->toBe('in_progress')
        ->and((int) $entry->user_id)->toBe($world['coordinator']->id)
        ->and(json_decode((string) $entry->old_values, true))->toBe(['status' => 'new'])
        ->and(json_decode((string) $entry->new_values, true))->toBe(['status' => 'in_progress']);
})->with(['the screen' => [false], 'the API' => [true]]);

test('opening it again changes nothing and writes nothing more', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();
    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();

    expect(supportAuditActions($request))->toBe(['support.status_changed']);
});

test('opening a request in any other state leaves it as it is', function (string $status): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => $status, 'rejection_reason' => $status === 'rejected' ? 'Not a platform issue' : null]);

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->assertOk();

    expect($request->fresh()?->status->value)->toBe($status)
        ->and(supportAuditActions($request))->toBe([]);
})->with(['in_progress', 'resolved', 'closed', 'rejected']);

test('the URL takes the reference in any form a person would type it, and never the id', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    foreach ([$request->display_reference, $request->reference, strtolower($request->display_reference)] as $form) {
        $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$form)->assertOk();
    }

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->id)->assertNotFound();
});

test('a second person opening a request someone has just opened gets the page, not a refusal', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    // The race, frozen: B's binding read `new`; A's open committed before B's transition locked the row.
    $staleForB = $request->fresh();
    app(App\Modules\Support\Actions\TransitionSupportRequest::class)->openIfNew($request, $world['admin']);

    $result = app(App\Modules\Support\Actions\TransitionSupportRequest::class)->openIfNew($staleForB, $world['coordinator']);

    expect($result->status->value)->toBe('in_progress')
        ->and(supportAuditActions($request))->toBe(['support.status_changed']);

    // And through the screen: the page renders, with the one entry A wrote.
    $this->actingAs($world['coordinator'])->get('/support-requests/'.$request->reference)->assertOk();

    expect(supportAuditActions($request))->toBe(['support.status_changed']);
});
