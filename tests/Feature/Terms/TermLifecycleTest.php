<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Modules\AcademicTerms\Actions\ChangeTermStatus;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T171 — the term state machine (data-model.md)
|--------------------------------------------------------------------------
|
|   draft ──► active ──► closed ──► archived
|     ▲          │           │
|     └──────────┘           └──► active   (reopen; Super Admin + mandatory reason)
|
| Both directions are asserted. Testing only the permitted moves would pass against a controller
| that assigns the status without checking anything at all.
|
*/

function aTerm(string $status = 'draft'): AcademicTerm
{
    return AcademicTerm::factory()->create(['status' => $status, 'is_current' => false]);
}

test('a term is created in draft, whatever the request says', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $response = $this->actingAs($admin)->postJson('/api/v1/terms', [
        'name_ar' => 'الفصل الأول 1448',
        'name_en' => 'First term 1448',
        'academic_year' => '2026/2027',
        'semester' => 'first',
        'start_date' => '2026-08-23',
        'end_date' => '2026-12-17',

        // Ignored: a term that could be born active would make its first transition unauditable.
        'status' => 'active',
        'is_current' => true,
    ])->assertCreated();

    expect($response->json('data.status'))->toBe('draft')
        ->and($response->json('data.is_current'))->toBeFalse();
});

test('a permitted transition is accepted and audited with both states', function (string $from, string $to): void {
    $admin = aUser('SUPER_ADMIN');
    $term = aTerm($from);

    DB::table('audit_logs')->delete();

    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => $to, 'reason' => 'نهاية الفترة'])
        ->assertOk()
        ->assertJsonPath('data.status', $to);

    $entry = DB::table('audit_logs')->where('action', 'term.status_changed')->first();

    expect($entry)->not->toBeNull();
    expect(json_decode((string) $entry->old_values, true)['status'])->toBe($from);
    expect(json_decode((string) $entry->new_values, true)['status'])->toBe($to);
})->with([
    'draft to active' => ['draft', 'active'],
    'active back to draft' => ['active', 'draft'],
    'active to closed' => ['active', 'closed'],
    'closed to archived' => ['closed', 'archived'],
    'closed reopened' => ['closed', 'active'],
]);

test('a disallowed transition is 409, and the term does not move', function (string $from, string $to): void {
    $admin = aUser('SUPER_ADMIN');
    $term = aTerm($from);

    /*
     * 409, not 422. The payload is well formed and the term exists; what is wrong is the state of
     * the thing being acted on. A 422 would send the caller hunting for a mistake in their own
     * request that is not there.
     */
    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => $to, 'reason' => 'أيّ سبب'])
        ->assertStatus(409);

    expect($term->fresh()->status)->toBe($from);
})->with([
    'draft straight to archived' => ['draft', 'archived'],
    'draft straight to closed' => ['draft', 'closed'],
    'active straight to archived' => ['active', 'archived'],
    'archived back to active' => ['archived', 'active'],
    'archived back to draft' => ['archived', 'draft'],
]);

test('reopening a closed term requires a reason', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $term = aTerm('closed');

    // A field error on `reason` (T263): the dialog shows it under the textarea. It was a 409 before,
    // which a browser shows as a banner away from the field that needs filling in.
    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'active'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason' => __('terms.errors.reason_required')]);

    // Too short to be a reason, as the dialog's minlength says.
    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'active', 'reason' => 'بب'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    expect($term->fresh()->status)->toBe('closed');

    /*
     * Closing a term is how an institution says these records are final. Undoing it without saying
     * why would make the statement worthless, so the reason is stored in the entry.
     */
    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'active', 'reason' => 'تصحيح درجة'])
        ->assertOk();

    $entry = DB::table('audit_logs')->where('action', 'term.status_changed')->latest('id')->first();

    expect(json_decode((string) $entry->new_values, true)['reason'])->toBe('تصحيح درجة');
});

test('reopening needs term.edit_closed, which a coordinator does not hold', function (): void {
    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->givePermissionTo('term.change_status');
    $coordinator->forgetScopeCache();

    $term = aTerm('closed');

    // They may change a status in general, and still not reopen a closed term.
    $this->actingAs($coordinator->fresh())
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'active', 'reason' => 'محاولة'])
        ->assertForbidden();

    /*
     * Archiving is the transition out of closed they may make — with a reason, because the contract
     * requires one for *any* exit from closed, not only the reopen. Closing is how an institution
     * says these records are final; both ways back out of that are deliberate acts.
     */
    $this->actingAs($coordinator->fresh())
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'archived'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    $this->actingAs($coordinator->fresh())
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'archived', 'reason' => 'انتهت فترة التصحيح'])
        ->assertOk();
});

test('the transition map refuses by default', function (): void {
    /*
     * Asserted as a property of the map rather than through the endpoint: the point is that a state
     * added to the enum without a row here is refused, not permitted by omission.
     */
    foreach (['draft', 'active', 'closed', 'archived'] as $from) {
        expect(ChangeTermStatus::permits($from, 'nonsense'))->toBeFalse();
    }

    expect(ChangeTermStatus::TRANSITIONS['archived'])->toBe([]);
});
