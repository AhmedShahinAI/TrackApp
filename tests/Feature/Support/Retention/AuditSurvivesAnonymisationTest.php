<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T454 — anonymising a request does not touch its audit trail (FR-160)
|--------------------------------------------------------------------------
|
| The audit entries follow the five-year audit retention, which is longer than the request's own two.
| The sweep adds one entry of its own, and that entry must not carry what the sweep just removed.
|
*/

test('every entry about the request survives the sweep unchanged', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['admin'], 25);
    $before = DB::table('audit_logs')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();

    expect(supportAuditActions($request))->toBe(['support.responded', 'support.status_changed']);

    runRetentionSweep();

    $after = DB::table('audit_logs')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();

    expect(array_slice($after, 0, count($before)))->toBe($before)
        ->and(supportAuditActions($request))->toBe(['support.responded', 'support.status_changed', 'support.anonymised']);
});

test('the sweep writes one system entry that does not keep what it removed', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['admin'], 25, 'reach-me@example.test');
    $ip = (string) $request->submitted_ip;

    runRetentionSweep();

    $entry = DB::table('audit_logs')->where('action', 'support.anonymised')->sole();
    $payload = (string) $entry->old_values.(string) $entry->new_values;

    expect($ip)->not->toBe('')
        ->and($entry->user_id)->toBeNull()
        ->and($entry->entity_type)->toBe('SupportRequest')
        ->and((int) $entry->entity_id)->toBe($request->id)
        ->and(json_decode((string) $entry->new_values, true)['channel'])->toBe('system')
        ->and($entry->old_values)->toBeNull()
        ->and(str_contains($payload, $ip))->toBeFalse()
        ->and(str_contains($payload, 'reach-me@example.test'))->toBeFalse()
        ->and(str_contains($payload, 'student_id'))->toBeFalse();
});
