<?php

declare(strict_types=1);

use App\Models\SectionEnrollment;

/*
|--------------------------------------------------------------------------
| T219 — 10,000 rows checked and loaded within 5 minutes (SC-007)
|--------------------------------------------------------------------------
|
| In the performance group, excluded from the default run (phpunit.xml): it builds and imports a
| ten-thousand-row roster end to end through the real endpoints, which is the point and also why it
| does not run on every push. Run it with:
|
|     php artisan test --group=performance tests/Performance/ImportThroughputTest.php
|
| Measured from the first byte of upload to `completed`, with validation and commit both on the
| clock, because SC-007 is the operator's wait and the operator waits for both. The queue runs
| synchronously here, so this measures the work itself; a deployment adds only queue latency.
|
| The roster is shaped like a real one — 250 sections of 40 — so the per-batch section and
| enrollment lookups are exercised at their realistic fan-out rather than against one section.
|
*/

test('a 10,000-row roster validates and commits in under five minutes', function (): void {
    $world = importWorld();

    $rows = [];

    for ($i = 0; $i < 10000; $i++) {
        $rows[] = rosterRow($world, [
            'section_number' => (string) (1000 + intdiv($i, 40)),
            'university_id' => '45'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
        ]);
    }

    $file = rosterFile($rows);

    $started = microtime(true);

    $job = importRoster($world, [], [], $file);

    $elapsed = microtime(true) - $started;

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(10000)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(10000);

    fwrite(STDERR, sprintf("\n  10,000 rows validated and committed in %.1f s (budget 300 s)\n", $elapsed));

    expect($elapsed)->toBeLessThan(300.0);
})->group('performance');
