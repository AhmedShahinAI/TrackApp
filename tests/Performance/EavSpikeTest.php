<?php

declare(strict_types=1);

use Database\Seeders\EavSpikeSeeder;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T290 — the typed EAV model at 1,500,000 values against R-06's five thresholds (SC-017, FR-087)
|--------------------------------------------------------------------------
|
|   grid                 one assignment's 200 students in roster order × 15 fields, pivoted   < 2 s
|   section_completion   completion over active targets for each of ≈3,000 sections           < 3 s
|   weighted_completion  initiative-wide, student-weighted, 30,000 targets                    < 3 s
|   upsert               one value saved: completion, author and audit entry in one txn      < 500 ms
|   recalculation        every record's completion re-derived from its values, written back   < 2 min
|
| Ten runs per query, each after eavSpikeColdStart() — a fresh connection, and with
| EAV_SPIKE_COLD_COMMAND an emptied cache. With ten samples the "p95" is the slowest run: the
| conservative reading. Numbers, plans, row counts and the machine go to
| storage/app/eav-spike-results.json, from which spike-eav-results.md is written (T292).
|
|     php artisan test --group=performance tests/Performance/EavSpikeTest.php
|     EAV_SPIKE_INDEXES=false php artisan test --group=performance tests/Performance/EavSpikeTest.php
|     EAV_SPIKE_MYSQL_DATABASE=track_eav_spike php artisan test --group=performance tests/Performance/EavSpikeTest.php
|
*/

const EAV_SPIKE_RUNS = 10;

test('the typed EAV model meets every R-06 threshold at 1,500,000 values', function (): void {
    $engine = eavSpikeDatabase();
    $seedSeconds = eavSpikeIsSeeded() ? null : eavSpikeSeed();
    eavSpikeAnalyze();
    $db = DB::connection(EAV_SPIKE_CONNECTION);

    $counts = collect(['sections', 'students', 'section_enrollments', 'initiatives', 'initiative_schema_versions', 'initiative_fields',
        'initiative_sections', 'initiative_targets', 'student_initiative_records', 'student_initiative_values'])
        ->mapWithKeys(fn (string $table) => [$table => $db->table($table)->count()])->all();

    $expectedValues = (int) round(EavSpikeSeeder::STUDENTS * (float) config('eav_spike.scale', 1.0)) * array_sum(EavSpikeSeeder::FIELD_MIX);
    expect($counts['student_initiative_values'])->toBe($expectedValues);

    // What the schema leaves to the application, checked on the seeded data (open question Q-1).
    $integrity = eavSpikeIntegrity();
    expect($integrity)->toBe(['multi_typed' => 0, 'foreign_field' => 0]);

    $targets = eavSpikeTargets();
    mt_srand(7);

    $results = [];
    $sweepAgrees = null;

    foreach (eavSpikeQueries($targets) as $name => $query) {
        $plans = array_map(fn (Closure $plan): string => eavSpikeExplain($plan()), $query['plans']);

        // The sweep must re-derive exactly the records the stored flags call complete (the upserts
        // before it keep those flags right). Taken before it runs, compared after every run.
        $expectedComplete = $name === 'recalculation'
            ? $db->table('student_initiative_records')->where('is_complete', true)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : null;

        $durations = [];
        $outputs = [];

        for ($i = 0; $i < EAV_SPIKE_RUNS; $i++) {
            eavSpikeColdStart();
            $started = hrtime(true);
            $outputs[] = ($query['run'])();
            $durations[] = (hrtime(true) - $started) / 1e9;
        }

        if ($expectedComplete !== null) {
            $sweepAgrees = collect($outputs)->every(fn (array $ids): bool => $ids === $expectedComplete);
        }

        sort($durations);
        $p95 = $durations[(int) ceil(EAV_SPIKE_RUNS * 0.95) - 1];
        $last = end($outputs);

        $results[$name] = [
            'threshold_s' => $query['threshold'],
            'p95_s' => round($p95, 4),
            'median_s' => round($durations[intdiv(EAV_SPIKE_RUNS, 2)], 4),
            'min_s' => round($durations[0], 4),
            'result' => is_array($last) ? count($last) : $last,
            'pass' => $p95 < $query['threshold'],
            'plans' => $plans,
        ];
    }

    file_put_contents(storage_path('app/eav-spike-results.json'), json_encode([
        'measured_at' => now()->toIso8601String(),
        'environment' => eavSpikeEnvironment($engine),
        'measurement_indexes' => eavSpikeWithIndexes(),
        'scale' => (float) config('eav_spike.scale', 1.0),
        'seeded_in_s' => $seedSeconds === null ? 'reused' : round($seedSeconds, 1),
        'runs_per_query' => EAV_SPIKE_RUNS,
        'row_counts' => $counts,
        'integrity' => $integrity,
        'sweep_agrees_with_stored_flags' => $sweepAgrees,
        'queries' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    fwrite(STDERR, "\n  EAV spike on {$engine}".(eavSpikeWithIndexes() ? ' (with measurement indexes)' : ' (without)')
        .($seedSeconds === null ? ', reused seed' : sprintf(', seeded in %.0f s', $seedSeconds)).":\n");
    foreach ($results as $name => $r) {
        fwrite(STDERR, sprintf("    %-20s p95 %8.3f s  median %8.3f s  (threshold %6.1f s)  %s\n", $name, $r['p95_s'], $r['median_s'], $r['threshold_s'], $r['pass'] ? 'PASS' : 'FAIL'));
    }

    expect($results['grid']['result'])->toBe(EavSpikeSeeder::GRID_SECTION_STUDENTS)
        ->and($sweepAgrees)->toBeTrue()
        ->and(collect($results)->reject(fn ($r) => $r['pass'])->keys()->all())->toBe([]);
})->group('performance');
