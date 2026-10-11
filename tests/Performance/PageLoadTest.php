<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T267 — every screen is ready to paint within 2 seconds at p95 (SC-015), server side
|--------------------------------------------------------------------------
|
| What is measured: the server's time to produce each screen's complete HTML — the part of first
| contentful paint this application controls. The browser's share (CSS, fonts, layout) is measured by
| tests/Visual/fcp.spec.ts; neither alone proves SC-015, and the two together bound it.
|
| p95 per screen and locale, over twenty warm renders each — not pooled. Pooled over 23 screens, one
| screen taking five seconds on every render is 4% of the samples and sits above the pooled p95, so
| the budget it breaks would pass.
|
| On the fixture dataset plus the volume the screens are built for: a 400-student roster on section
| 13 and 5,000 audit entries, so a list that loads everything instead of a page shows up here. SQLite
| in-process, so the numbers are a floor for a networked database, not a forecast of one.
|
|     php artisan test --group=performance tests/Performance/PageLoadTest.php
|
*/

const RENDERS_PER_SCREEN = 20;

function seedScreenVolume(): void
{
    $now = now();

    foreach (array_chunk(range(1, 400), 200) as $chunk) {
        DB::table('students')->insert(array_map(static fn (int $i): array => [
            'university_id' => (string) (449000000 + $i),
            'name_ar' => 'طالب اختبار '.$i,
            'name_en' => 'Load Student '.$i,
            'created_at' => $now,
            'updated_at' => $now,
        ], $chunk));
    }

    $students = DB::table('students')->where('university_id', '>=', '449000001')->pluck('id');

    foreach ($students->chunk(200) as $chunk) {
        DB::table('section_enrollments')->insert($chunk->values()->map(static fn (int $id, int $i): array => [
            'student_id' => $id,
            'section_id' => 13,
            'status' => 'active',
            'enrolled_at' => $now,
            'roster_sequence' => 1000 + $i,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    foreach (array_chunk(range(1, 5000), 500) as $chunk) {
        DB::table('audit_logs')->insert(array_map(static fn (int $i): array => [
            'user_id' => 1,
            'action' => 'auth.login',
            'ip_address' => '10.0.0.1',
            'created_at' => $now->copy()->subMinutes($i),
        ], $chunk));
    }
}

test('every screen renders within 2 seconds at p95, each screen on its own', function (): void {
    seedScreens();
    seedScreenVolume();

    $over = [];
    $report = [];

    foreach (array_keys(screens()) as $key) {
        foreach (['ar', 'en'] as $locale) {
            renderScreen($key, $locale)->assertOk(); // warm the view cache, as a running server has

            $durations = [];

            for ($i = 0; $i < RENDERS_PER_SCREEN; $i++) {
                $started = hrtime(true);
                renderScreen($key, $locale)->assertOk();
                $durations[] = (hrtime(true) - $started) / 1e6;
            }

            sort($durations);
            $p95 = $durations[(int) ceil(RENDERS_PER_SCREEN * 0.95) - 1];
            $report["{$key} · {$locale}"] = $p95;

            if ($p95 >= 2000.0) {
                $over[] = sprintf('%s · %s: p95 %.0f ms', $key, $locale, $p95);
            }
        }
    }

    arsort($report);
    fwrite(STDERR, "\n  slowest p95: ".implode(', ', array_map(
        static fn (string $k, float $v): string => sprintf('%s %.0f ms', $k, $v),
        array_keys(array_slice($report, 0, 5)),
        array_slice($report, 0, 5),
    ))."\n");

    expect($over)->toBe([]);
})->group('performance');
