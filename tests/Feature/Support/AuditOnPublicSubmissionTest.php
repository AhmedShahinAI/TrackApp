<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T386 — a public submission is audited once, honestly, and without the student's words
|--------------------------------------------------------------------------
|
| Exactly one `support.request_created`, with a null actor (there is no signed-in author, and the
| entry does not invent one), `channel: public`, the originating IP, and the resolved student and term
| (contracts/support-public.md). And never the description, the contact, the challenge solution or
| the honeypot: the audit log keeps five years, the request's text two (R-12).
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('one entry, null actor, channel public, the address, the student and the term', function (): void {
    $student = enrolledStudent('441234567');
    DB::table('audit_logs')->delete();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
        ->post('/support', supportSubmission([
            'description' => 'Private words about my schedule',
            'contact' => '0555123456',
        ]))
        ->assertRedirect(route('support.submitted'));

    $request = SupportRequest::query()->sole();
    $entries = DB::table('audit_logs')->get();

    expect($entries)->toHaveCount(1);

    $entry = $entries[0];
    $values = json_decode((string) $entry->new_values, true);

    expect($entry->action)->toBe('support.request_created')
        ->and($entry->user_id)->toBeNull()
        // The row's own ip_address column reads 0.0.0.0 under the test runner (Auditor: console means
        // no request); the contract's IP is the payload's, which is the request's own.
        ->and((int) $entry->entity_id)->toBe($request->id)
        ->and($values['channel'])->toBe('public')
        ->and($values['submitted_ip'])->toBe('203.0.113.77')
        ->and($values['student_id'])->toBe($student->id)
        ->and($values['term_id'])->toBe($request->term_id);
});

test('the entry carries no description, contact, challenge solution or honeypot', function (): void {
    enrolledStudent('441234567');
    DB::table('audit_logs')->delete();

    $payload = supportSubmission([
        'description' => 'Private words about my schedule',
        'contact' => '0555123456',
    ]);

    $this->post('/support', $payload)->assertRedirect(route('support.submitted'));

    $entry = DB::table('audit_logs')->sole();
    $written = (string) $entry->old_values.(string) $entry->new_values.(string) $entry->user_agent;

    foreach (['Private words', '0555123456', $payload['challenge'], '"challenge_solution"', '_honeypot', '_issued_at'] as $secret) {
        expect($written)->not->toContain((string) $secret);
    }
});

test('a refused submission writes no entry at all', function (): void {
    DB::table('audit_logs')->delete();

    $this->post('/support', supportSubmission(['university_id' => '441000001']))->assertStatus(422);
    $this->post('/support', supportSubmission(['_honeypot' => 'bot']))->assertRedirect();

    expect(DB::table('audit_logs')->count())->toBe(0);
});

test('when the audit entry cannot be written, the request is not recorded either (constitution II)', function (): void {
    enrolledStudent('441234567');
    $this->withoutExceptionHandling();

    // The audit table gone: the Auditor's insert fails inside the action's transaction.
    Illuminate\Support\Facades\Schema::rename('audit_logs', 'audit_logs_unavailable');

    try {
        $this->post('/support', supportSubmission());
        $this->fail('The submission succeeded without its audit entry.');
    } catch (Illuminate\Database\QueryException) {
        // Expected: the transaction rolled back.
    } finally {
        Illuminate\Support\Facades\Schema::rename('audit_logs_unavailable', 'audit_logs');
    }

    expect(SupportRequest::query()->count())->toBe(0);
});
