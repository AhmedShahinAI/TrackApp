<?php

declare(strict_types=1);

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T161 — a person + date-range filter over a million entries (SC-004)
|--------------------------------------------------------------------------
|
| contracts/audit.md sizes it: "filtering by user_id plus a date range over 1,000,000 rows returns
| within 2 seconds, served by the (user_id, created_at) index."
|
| The number is only half the test. Measuring 1.8 seconds proves nothing about why — a full scan on
| a warm page cache can beat an index on a cold one, and the next ten thousand rows reverse it. So
| the plan is asserted as well: the query must use the composite index, not merely finish in time.
| A regression that drops the index would otherwise pass here for months and fail in production on
| the day the table outgrew memory.
|
| In the performance group, excluded from the default run: seeding a million rows on every push
| would make CI unusable, and principle VII asks for the budget to be measured before acceptance,
| not continuously.
|
*/

/**
 * Seed `$count` entries spread across a year, all but a slice belonging to other actors.
 *
 * Inserted in bulk through the query builder. Going through the model would fire the Auditable
 * observers and take hours; the Auditor writes through the query builder too, so this is the same
 * path production uses, at volume.
 */
function seedAuditEntries(int $count, int $actorId, int $otherActorId): void
{
    $rows = [];
    $start = now()->subYear();

    for ($i = 0; $i < $count; $i++) {
        // One row in forty belongs to the actor under test, so the filter is selective but not rare.
        $rows[] = [
            'user_id' => $i % 40 === 0 ? $actorId : $otherActorId,
            'action' => 'section.updated',
            'entity_type' => 'Section',
            'entity_id' => $i % 5000,
            'ip_address' => '10.0.0.1',
            'created_at' => $start->copy()->addMinutes($i % 525_600),
        ];

        if (count($rows) === 2000) {
            DB::table('audit_logs')->insert($rows);
            $rows = [];
        }
    }

    if ($rows !== []) {
        DB::table('audit_logs')->insert($rows);
    }
}

test('a person plus date-range filter over a million entries returns inside two seconds', function (): void {
    $actor = aUser('SUPER_ADMIN');
    $other = aUser('INSTRUCTOR');

    seedAuditEntries(1_000_000, $actor->id, $other->id);

    expect(DB::table('audit_logs')->count())->toBeGreaterThanOrEqual(1_000_000);

    $from = now()->subMonths(3);
    $to = now()->subMonth();

    $started = hrtime(true);

    $page = AuditLog::query()
        ->where('user_id', $actor->id)
        ->whereBetween('created_at', [$from, $to])
        ->orderByDesc('created_at')
        ->paginate(25);

    $elapsed = (hrtime(true) - $started) / 1_000_000_000;

    expect($page->count())->toBeGreaterThan(0);

    expect($elapsed)->toBeLessThan(
        2.0,
        sprintf('SC-004: the filter took %.3fs over %d rows', $elapsed, DB::table('audit_logs')->count()),
    );
})->group('performance');

test('the filter is served by the (user_id, created_at) index rather than a scan', function (): void {
    $actor = aUser('SUPER_ADMIN');
    $other = aUser('INSTRUCTOR');

    seedAuditEntries(50_000, $actor->id, $other->id);

    $driver = DB::connection()->getDriverName();

    $sql = 'select * from audit_logs where user_id = ? and created_at between ? and ? order by created_at desc limit 25';
    $bindings = [$actor->id, now()->subMonths(3), now()->subMonth()];

    $plan = match ($driver) {
        'mysql' => collect(DB::select('EXPLAIN '.$sql, $bindings))->pluck('key')->implode(','),
        'sqlite' => collect(DB::select('EXPLAIN QUERY PLAN '.$sql, $bindings))->pluck('detail')->implode(' '),
        default => null,
    };

    if ($plan === null) {
        $this->markTestSkipped("No plan reader for the '{$driver}' driver.");
    }

    /*
     * Asserted as "an index is used", not as a specific index name: MySQL and SQLite name them
     * differently and the migration may rename one. What must never be true is a full scan, which
     * is what both engines report by its absence.
     */
    expect(strtolower($plan))->toContain('index')
        ->and(strtolower($plan))->not->toContain('scan audit_logs')
        ->and($plan)->not->toBe('');
})->group('performance');

test('the list endpoint itself stays inside the budget at volume', function (): void {
    $actor = aUser('SUPER_ADMIN');
    $other = aUser('INSTRUCTOR');

    seedAuditEntries(1_000_000, $actor->id, $other->id);

    $started = hrtime(true);

    $this->actingAs($actor)
        ->getJson('/api/v1/audit-logs?user_id='.$actor->id
            .'&date_from='.now()->subMonths(3)->toDateString()
            .'&date_to='.now()->subMonth()->toDateString())
        ->assertOk();

    $elapsed = (hrtime(true) - $started) / 1_000_000_000;

    /*
     * The endpoint, not just the query — the budget a person experiences includes the resource
     * mapping and the entity labels, and `entity_label` resolves a model per row. Twenty-five rows
     * of labels is twenty-five lookups, which is the kind of thing that passes at ten rows and
     * fails at a page.
     */
    expect($elapsed)->toBeLessThan(2.0, sprintf('the endpoint took %.3fs', $elapsed));
})->group('performance');
