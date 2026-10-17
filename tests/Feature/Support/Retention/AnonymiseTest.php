<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Modules\Settings\Settings;
use App\Modules\Support\Jobs\AnonymiseExpiredRequestsJob;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T453 — the retention sweep clears exactly three columns and keeps everything else (SC-037, FR-159)
|--------------------------------------------------------------------------
*/

test('a request past its retention loses its student, contact and address, and nothing else', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['admin'], 25);
    $before = (array) DB::table('support_requests')->where('id', $request->id)->first();
    $responses = DB::table('support_responses')->where('support_request_id', $request->id)->get();

    expect(runRetentionSweep())->toBe(1);

    $after = (array) DB::table('support_requests')->where('id', $request->id)->first();

    expect($after['student_id'])->toBeNull()
        ->and($after['contact'])->toBeNull()
        ->and($after['submitted_ip'])->toBeNull()
        ->and($after['anonymised_at'])->not->toBeNull();

    // Every other column, unchanged: the reference, the category, the description, the state.
    $cleared = ['student_id', 'contact', 'submitted_ip', 'anonymised_at', 'updated_at'];

    expect(array_diff_key($after, array_flip($cleared)))->toBe(array_diff_key($before, array_flip($cleared)));

    expect($responses)->toHaveCount(1)
        ->and(DB::table('support_responses')->where('support_request_id', $request->id)->get())->toEqual($responses);

    // The state history is the request's status_changed entries, and they are still there.
    expect(supportAuditActions($request))->toContain('support.status_changed');
});

test('the clock runs from creation: a request inside its period is untouched, and an abandoned one is not kept', function (): void {
    $world = supportStaffWorld();
    $young = agedSupportRequest($world['branch'], $world['admin'], 23);
    $abandoned = supportRequestFor($world['branch']);
    $abandoned->forceFill(['created_at' => now()->subMonths(25)->subDay()])->saveQuietly();

    expect(runRetentionSweep())->toBe(1);

    expect($young->refresh()->anonymised_at)->toBeNull()
        ->and($young->student_id)->not->toBeNull()
        // Never closed, still `new`: retention does not wait for a closure that will not come.
        ->and($abandoned->refresh()->anonymised_at)->not->toBeNull()
        ->and($abandoned->status->value)->toBe('new');
});

test('the period is the setting, not a literal', function (): void {
    $world = supportStaffWorld();
    supportSetting(Settings::SUPPORT_RETENTION_MONTHS, 6);

    $request = agedSupportRequest($world['branch'], $world['admin'], 7);

    expect(runRetentionSweep())->toBe(1)
        ->and($request->refresh()->anonymised_at)->not->toBeNull();
});

test('a period below one month is refused rather than read as keep nothing', function (): void {
    $world = supportStaffWorld();
    supportSetting(Settings::SUPPORT_RETENTION_MONTHS, 0);
    supportRequestFor($world['branch']);

    expect(fn () => runRetentionSweep())->toThrow(RuntimeException::class);
    expect(SupportRequest::query()->withoutGlobalScopes()->whereNotNull('anonymised_at')->count())->toBe(0);
});

test('one row that cannot be anonymised does not stop the rest, and the run still fails loudly', function (): void {
    $world = supportStaffWorld();
    $first = supportRequestFor($world['branch']);
    $second = supportRequestFor($world['branch']);

    foreach ([$first, $second] as $request) {
        $request->forceFill(['created_at' => now()->subMonths(25)])->saveQuietly();
    }

    // The first row's audit entry is refused by the database, so its transaction rolls back
    // (constitution II). It comes first in id order, which is where it used to stop the whole sweep.
    DB::unprepared("CREATE TRIGGER refuse_first BEFORE INSERT ON audit_logs
        WHEN NEW.action = 'support.anonymised' AND NEW.entity_id = {$first->id}
        BEGIN SELECT RAISE(ABORT, 'refused'); END");

    expect(fn () => runRetentionSweep())->toThrow(RuntimeException::class, '1 could not be anonymised');

    expect($first->refresh()->anonymised_at)->toBeNull()
        ->and($first->student_id)->not->toBeNull()
        ->and($second->refresh()->anonymised_at)->not->toBeNull();
});

test('a row that bypassed a staff rule is still anonymised', function (): void {
    $world = supportStaffWorld();

    // Rejected with no reason: the model refuses to save one, and an import or old row may still hold it.
    $request = supportRequestFor($world['branch'], ['status' => 'rejected', 'rejection_reason' => null]);
    $request->forceFill(['created_at' => now()->subMonths(25)])->saveQuietly();

    expect(runRetentionSweep())->toBe(1)
        ->and($request->refresh()->anonymised_at)->not->toBeNull();
});

test('the cutoff is inclusive and counts calendar months, a month-end included', function (): void {
    $world = supportStaffWorld();
    $now = Illuminate\Support\Carbon::parse('2026-02-28 12:00:00');

    // 24 months before 28 February 2026 is 28 February 2024: on it is expired, a second later is not.
    $on = supportRequestFor($world['branch']);
    $on->forceFill(['created_at' => '2024-02-28 12:00:00'])->saveQuietly();
    $after = supportRequestFor($world['branch']);
    $after->forceFill(['created_at' => '2024-02-28 12:00:01'])->saveQuietly();

    app('auth')->forgetGuards();

    expect(app(App\Modules\Support\Actions\AnonymiseExpiredRequests::class)->execute($now))->toBe(1)
        ->and($on->refresh()->anonymised_at)->not->toBeNull()
        ->and($after->refresh()->anonymised_at)->toBeNull();
});

test('a second sweep changes nothing and writes nothing', function (): void {
    $world = supportStaffWorld();
    agedSupportRequest($world['branch'], $world['admin'], 25);
    runRetentionSweep();
    $entries = DB::table('audit_logs')->count();

    expect(runRetentionSweep())->toBe(0)
        ->and(DB::table('audit_logs')->count())->toBe($entries);
});

test('the queued job runs the sweep', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['admin'], 25);

    AnonymiseExpiredRequestsJob::dispatchSync();

    expect($request->refresh()->anonymised_at)->not->toBeNull();
});

test('the sweep is scheduled once, daily, on one server, as the queued job', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => $event->description === 'support:anonymise');

    expect($events)->toHaveCount(1);

    $event = $events->first();

    expect($event->expression)->toBe('0 0 * * *')
        ->and($event->onOneServer)->toBeTrue();

    // Schedule::job() dispatches what it was given; running the event must queue this job.
    Illuminate\Support\Facades\Queue::fake();
    $event->run(app());
    Illuminate\Support\Facades\Queue::assertPushed(AnonymiseExpiredRequestsJob::class);
});

test('the job is unique for a day and tried three times', function (): void {
    $job = new AnonymiseExpiredRequestsJob;

    expect($job)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldBeUnique::class)
        ->and($job)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class)
        ->and($job->uniqueFor)->toBe(86_400)
        ->and($job->tries)->toBe(3);
});

test('the sweep and the overdue check have their index: not anonymised, by creation date', function (): void {
    $index = collect(Illuminate\Support\Facades\Schema::getIndexes('support_requests'))
        ->firstWhere('name', 'support_requests_retention_index');

    expect($index)->not->toBeNull()
        ->and($index['columns'])->toBe(['anonymised_at', 'created_at']);
});
