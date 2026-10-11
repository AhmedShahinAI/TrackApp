<?php

declare(strict_types=1);

use Database\Seeders\EavSpikeSeeder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/*
|--------------------------------------------------------------------------
| Phase 12 — the typed EAV capacity spike's database and its five queries (research.md R-06)
|--------------------------------------------------------------------------
|
| Test support only: the spike's queries are what Phase 2 will run, written here so they are measured,
| not shipped. Nothing in app/ references the Group 6 tables (EavSpikeContainmentTest).
|
| ## Where it runs
|
| On its own connection, never the test suite's in-memory database and never the application's:
|
|   - default: a SQLite file, storage/app/eav-spike.sqlite (git-ignored), seeded once and reused while
|     its row counts and index set are right;
|   - EAV_SPIKE_MYSQL_DATABASE=<schema>: that schema on the `mysql` connection's server — the engine
|     production runs, and the measurement that decides. It is created if missing and dropped/reseeded
|     by the spike, so it must be a schema of its own: the name is refused if it matches any configured
|     connection's database or a MySQL system schema, compared case-insensitively.
|
| ## "Cold"
|
| Each measured run opens a fresh connection, so nothing is cached in the connection. That is not a
| cold cache: the OS page cache (SQLite) and the InnoDB buffer pool (MySQL) stay warm. Set
| EAV_SPIKE_COLD_COMMAND to a command that empties them — on a MySQL host, restarting the server — and
| it runs before every measured run; the results record which kind of run was made.
|
*/

const EAV_SPIKE_CONNECTION = EavSpikeSeeder::CONNECTION;

/** MySQL schemas the spike will never use, whatever the configuration says. */
const EAV_SPIKE_SYSTEM_SCHEMAS = ['mysql', 'sys', 'information_schema', 'performance_schema'];

/** Whether a schema name is unsafe for the spike to drop and reseed. */
function eavSpikeSchemaIsForbidden(string $schema): bool
{
    $normal = static fn (mixed $name): string => strtolower(trim(str_replace('`', '', (string) $name)));
    $wanted = $normal($schema);

    if ($wanted === '' || in_array($wanted, EAV_SPIKE_SYSTEM_SCHEMAS, true)) {
        return true;
    }

    foreach ((array) config('database.connections') as $name => $connection) {
        if ($name === EAV_SPIKE_CONNECTION || ! is_array($connection) || ! isset($connection['database'])) {
            continue;
        }

        if ($normal($connection['database']) === $wanted) {
            return true;
        }
    }

    return false;
}

/** Point the eav_spike connection at its database; returns the engine name. */
function eavSpikeDatabase(): string
{
    $schema = (string) config('eav_spike.mysql_database', '');

    if ($schema !== '') {
        if (eavSpikeSchemaIsForbidden($schema)) {
            throw new RuntimeException("EAV_SPIKE_MYSQL_DATABASE \"{$schema}\" is an application or system schema; the spike drops and reseeds its schema, so it needs one of its own.");
        }

        $clean = str_replace('`', '', $schema);

        // The schema may not exist yet: create it through a connection that names no database.
        config(['database.connections.eav_spike_server' => ['database' => null] + (array) config('database.connections.mysql')]);
        DB::connection('eav_spike_server')->statement('create database if not exists `'.$clean.'` character set utf8mb4 collate utf8mb4_unicode_ci');
        DB::purge('eav_spike_server');

        config(['database.connections.'.EAV_SPIKE_CONNECTION => ['database' => $clean] + (array) config('database.connections.mysql')]);
    } else {
        $file = storage_path('app/eav-spike.sqlite');

        if (! is_file($file)) {
            touch($file);
        }

        config(['database.connections.'.EAV_SPIKE_CONNECTION => [
            'driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
    }

    DB::purge(EAV_SPIKE_CONNECTION);

    return (string) DB::connection(EAV_SPIKE_CONNECTION)->getDriverName();
}

/** Whether the run measures with the two indexes under test (EAV_SPIKE_INDEXES, default yes). */
function eavSpikeWithIndexes(): bool
{
    return (bool) config('eav_spike.measure_with_indexes', true);
}

/** Whether the spike database already holds the full seed for the requested index set. */
function eavSpikeIsSeeded(): bool
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);

    try {
        $values = $db->table('student_initiative_values')->count();
    } catch (Throwable) {
        return false;
    }

    $expected = (int) round(EavSpikeSeeder::STUDENTS * (float) config('eav_spike.scale', 1.0)) * array_sum(EavSpikeSeeder::FIELD_MIX);
    $marker = $db->getSchemaBuilder()->hasIndex('student_initiative_values', 'siv_field_bool_index');

    return $values === $expected && $marker === eavSpikeWithIndexes();
}

/** Migrate fresh and seed, on the spike connection only. Returns seconds taken. */
function eavSpikeSeed(): float
{
    $started = hrtime(true);
    $default = DB::getDefaultConnection();
    $indexes = config('eav_spike.measurement_indexes');

    try {
        // The measurement indexes are the spike's switch, applied to its own schema only; the
        // application's migrations leave them off (config/eav_spike.php).
        config(['eav_spike.measurement_indexes' => eavSpikeWithIndexes()]);
        DB::setDefaultConnection(EAV_SPIKE_CONNECTION);
        Artisan::call('migrate:fresh', ['--database' => EAV_SPIKE_CONNECTION, '--force' => true]);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA journal_mode = WAL');
        }

        (new EavSpikeSeeder)->run();
    } finally {
        DB::setDefaultConnection($default);
        config(['eav_spike.measurement_indexes' => $indexes]);
    }

    return (hrtime(true) - $started) / 1e9;
}

/**
 * Refresh the planner's statistics, as a bulk load leaves them stale. Production MySQL maintains
 * InnoDB statistics itself, recalculating once about a tenth of a table has changed — but in the
 * background, so a large import can be read before they catch up. SQLite has none until ANALYZE runs;
 * without them it chose the (field_id, value_bool) index for a one-record lookup — 150,000 rows walked
 * instead of five, 385 ms instead of 2 (spike-eav-results.md, finding F-1).
 */
function eavSpikeAnalyze(): void
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);

    if ($db->getDriverName() === 'sqlite') {
        $db->statement('ANALYZE');

        return;
    }

    foreach (['initiative_sections', 'initiative_targets', 'student_initiative_records', 'student_initiative_values'] as $table) {
        $db->statement('ANALYZE TABLE '.$table);
    }
}

/** Before a measured run: a fresh connection and, if configured, an emptied cache. */
function eavSpikeColdStart(): void
{
    $command = (string) config('eav_spike.cold_command', '');

    if ($command !== '') {
        Process::timeout(300)->run($command)->throw();
    }

    DB::purge(EAV_SPIKE_CONNECTION);

    // After a server restart the first connection can be refused for a moment.
    for ($attempt = 0; $attempt < 30; $attempt++) {
        try {
            DB::connection(EAV_SPIKE_CONNECTION)->select('select 1');

            return;
        } catch (Throwable $e) {
            DB::purge(EAV_SPIKE_CONNECTION);
            usleep(500_000);
        }
    }

    throw new RuntimeException('The spike database did not come back after the cold step.');
}

/**
 * The ids the queries address, read from the seeded data (so a reused database needs no seeder state).
 *
 * @return array{initiative: int, grid_assignment: int, grid_fields: list<int>, required: list<array{id: int, type: string}>, records: array{0: int, 1: int}, user: int}
 */
function eavSpikeTargets(): array
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);
    $initiative = (int) $db->table('initiative_sections')->min('initiative_id');
    $grid = (int) $db->table('student_initiative_records')->select('assignment_id')
        ->groupBy('assignment_id')->orderByRaw('count(*) desc')->value('assignment_id');
    $version = (int) $db->table('initiatives')->where('id', $initiative)->value('current_schema_version_id');

    return [
        'initiative' => $initiative,
        'grid_assignment' => $grid,
        'grid_fields' => $db->table('initiative_fields')->where('schema_version_id', $version)->where('visible_to_instructor', true)
            ->orderBy('sort_order')->limit(EavSpikeSeeder::GRID_FIELDS)->pluck('id')->map(fn ($id) => (int) $id)->all(),
        'required' => $db->table('initiative_fields')->where('schema_version_id', $version)->where('required_for_completion', true)
            ->get(['id', 'type'])->map(fn ($f) => ['id' => (int) $f->id, 'type' => (string) $f->type])->all(),
        'records' => [(int) $db->table('student_initiative_records')->min('id'), (int) $db->table('student_initiative_records')->max('id')],
        'user' => (int) $db->table('users')->min('id'),
    ];
}

/**
 * The completion rule Phase 2 derives (constitution III), as a query over records [from, to]: a
 * Yes/No field must be Yes; any other required field must hold a value. Returns the complete records.
 *
 * @param  list<array{id: int, type: string}>  $required
 */
function eavSpikeCompleteQuery(array $required, int $from, int $to): Builder
{
    $bool = array_column(array_filter($required, fn ($f) => $f['type'] === 'bool'), 'id');
    $other = array_column(array_filter($required, fn ($f) => $f['type'] !== 'bool'), 'id');

    return DB::connection(EAV_SPIKE_CONNECTION)->table('student_initiative_values')
        ->select('record_id')
        ->whereBetween('record_id', [$from, $to])
        ->whereIn('field_id', array_merge($bool, $other))
        ->where(function ($q) use ($bool, $other): void {
            $q->where(fn ($b) => $b->whereIn('field_id', $bool === [] ? [0] : $bool)->where('value_bool', true))
                ->orWhere(fn ($o) => $o->whereIn('field_id', $other === [] ? [0] : $other)->where(fn ($v) => $v
                    ->where('value_text', '<>', '')->orWhereNotNull('value_number')->orWhereNotNull('value_date')->orWhereNotNull('value_json')));
        })
        ->groupBy('record_id')
        ->havingRaw('count(*) = ?', [count($required)]);
}

/**
 * @param  list<array{id: int, type: string}>  $required
 * @return list<int>
 */
function eavSpikeCompleteRecords(array $required, int $from, int $to): array
{
    return eavSpikeCompleteQuery($required, $from, $to)->pluck('record_id')->map(fn ($id) => (int) $id)->all();
}

/**
 * R-06's five queries, each a closure doing the whole unit of work a screen or job would, and the
 * queries whose plans the results record.
 *
 * @param  array<string, mixed>  $t  eavSpikeTargets()
 * @return array<string, array{threshold: float, run: Closure, plans: array<string, Closure>}>
 */
function eavSpikeQueries(array $t): array
{
    $db = fn () => DB::connection(EAV_SPIKE_CONNECTION);

    // 1. The instructor's grid: the assignment's students in roster order, then the 15 visible fields'
    //    values for exactly those records — the two queries the screen makes.
    $roster = fn () => $db()->table('student_initiative_records as r')
        ->join('students as s', 's.id', '=', 'r.student_id')
        ->join('initiative_targets as t', fn ($j) => $j->on('t.assignment_id', '=', 'r.assignment_id')->on('t.student_id', '=', 'r.student_id'))
        ->where('r.assignment_id', $t['grid_assignment'])
        ->whereNull('t.removed_at')
        ->orderBy('s.name_ar')
        ->select('r.id', 'r.is_complete', 's.university_id', 's.name_ar', 's.name_en');

    $values = fn (array $recordIds) => $db()->table('student_initiative_values')
        ->whereIn('record_id', $recordIds)
        ->whereIn('field_id', $t['grid_fields'])
        ->select('record_id', 'field_id', 'value_text', 'value_number', 'value_date', 'value_bool', 'value_json');

    // 2. Completion per section across the initiative, over active targets, with the section's number
    //    and branch for the list it feeds.
    $sections = fn () => $db()->table('initiative_sections as a')
        ->join('sections as s', 's.id', '=', 'a.section_id')
        ->join('initiative_targets as t', 't.assignment_id', '=', 'a.id')
        ->leftJoin('student_initiative_records as r', fn ($j) => $j->on('r.assignment_id', '=', 't.assignment_id')->on('r.student_id', '=', 't.student_id'))
        ->where('a.initiative_id', $t['initiative'])
        ->whereNull('t.removed_at')
        ->groupBy('a.section_id', 's.section_number', 's.branch_id')
        ->select(['a.section_id', 's.section_number', 's.branch_id', DB::raw('count(t.id) as total'), DB::raw('sum(case when r.is_complete then 1 else 0 end) as complete')]);

    // 3. Initiative-wide, student-weighted.
    $weighted = fn () => $db()->table('initiative_targets as t')
        ->join('initiative_sections as a', 'a.id', '=', 't.assignment_id')
        ->leftJoin('student_initiative_records as r', fn ($j) => $j->on('r.assignment_id', '=', 't.assignment_id')->on('r.student_id', '=', 't.student_id'))
        ->where('a.initiative_id', $t['initiative'])
        ->whereNull('t.removed_at')
        ->select([DB::raw('count(*) as targets'), DB::raw('sum(case when r.is_complete then 1 else 0 end) as complete')]);

    $sampleRecord = $t['records'][0] + 123;

    return [
        'grid' => [
            'threshold' => 2.0,
            'plans' => [
                'roster' => $roster,
                'values' => fn () => $values($roster()->limit(EavSpikeSeeder::GRID_SECTION_STUDENTS)->pluck('r.id')->all()),
            ],
            // One row per student, one column per field.
            'run' => function () use ($roster, $values): int {
                $students = $roster()->get();
                $pivot = [];

                foreach ($students as $student) {
                    $pivot[$student->id] = ['student' => $student, 'fields' => []];
                }

                foreach ($values($students->pluck('id')->all())->get() as $v) {
                    $pivot[$v->record_id]['fields'][$v->field_id] = $v->value_text ?? $v->value_number ?? $v->value_date ?? $v->value_bool ?? $v->value_json;
                }

                return count($pivot);
            },
        ],
        'section_completion' => [
            'threshold' => 3.0,
            'plans' => ['per_section' => $sections],
            // Every section's row fetched — the screen needs them all; counting them is incidental.
            'run' => fn (): int => count($sections()->get()->all()),
        ],
        'weighted_completion' => [
            'threshold' => 3.0,
            'plans' => ['weighted' => $weighted],
            'run' => fn (): int => (int) $weighted()->first()->targets,
        ],
        'upsert' => [
            'threshold' => 0.5,
            'plans' => ['completion_one_record' => fn () => eavSpikeCompleteQuery($t['required'], $sampleRecord, $sampleRecord)],
            /*
             * One answer saved, as the grid's write path will: the value upserted on its key, the
             * record's completion re-derived with its completed_at and author, and the change audited
             * — all in one transaction, because constitution II puts the audit entry in the same
             * transaction as the change it records.
             */
            'run' => function () use ($t, $db): int {
                $record = $t['records'][0] + mt_rand(0, $t['records'][1] - $t['records'][0]);
                // A required Yes/No field, written in its own typed column. (Writing value_bool onto
                // whichever required field came first once put a Yes/No answer on the text field —
                // two typed columns on one value, caught by eavSpikeIntegrity().)
                $field = array_values(array_filter($t['required'], fn (array $f): bool => $f['type'] === 'bool'))[0];
                $new = (bool) mt_rand(0, 1);

                $db()->transaction(function () use ($db, $t, $record, $field, $new): void {
                    $old = $db()->table('student_initiative_values')->where('record_id', $record)->where('field_id', $field['id'])->value('value_bool');

                    $db()->table('student_initiative_values')->upsert(
                        [['record_id' => $record, 'field_id' => $field['id'], 'value_bool' => $new, 'updated_by' => $t['user'], 'updated_at' => now()]],
                        ['record_id', 'field_id'],
                        ['value_bool', 'updated_by', 'updated_at'],
                    );

                    $complete = eavSpikeCompleteRecords($t['required'], $record, $record) !== [];
                    $db()->table('student_initiative_records')->where('id', $record)->update([
                        'is_complete' => $complete,
                        'completed_at' => $complete ? now() : null,
                        'updated_by' => $t['user'],
                        'updated_at' => now(),
                    ]);

                    $db()->table('audit_logs')->insert([
                        'user_id' => $t['user'],
                        'action' => 'initiative.value_updated',
                        'entity_type' => 'student_initiative_record',
                        'entity_id' => $record,
                        'old_values' => json_encode(['field_'.$field['id'] => $old]),
                        'new_values' => json_encode(['field_'.$field['id'] => $new, 'is_complete' => $complete]),
                        'ip_address' => '10.0.0.1',
                        'user_agent' => 'eav-spike',
                        'created_at' => now(),
                    ]);
                });

                return 1;
            },
        ],
        'recalculation' => [
            'threshold' => 120.0,
            'plans' => ['completion_1000_records' => fn () => eavSpikeCompleteQuery($t['required'], $t['records'][0], $t['records'][0] + 999)],
            // Every record's is_complete re-derived from its values and written back, a thousand
            // records per transaction. Returns the complete records' ids, so the test can check them.
            'run' => function () use ($t, $db): array {
                [$min, $max] = $t['records'];
                $complete = [];

                for ($from = $min; $from <= $max; $from += 1000) {
                    $to = min($max, $from + 999);
                    $ids = eavSpikeCompleteRecords($t['required'], $from, $to);
                    array_push($complete, ...$ids);

                    $db()->transaction(function () use ($db, $from, $to, $ids): void {
                        $db()->table('student_initiative_records')->whereBetween('id', [$from, $to])->update(['is_complete' => false]);

                        foreach (array_chunk($ids, 500) as $chunk) {
                            $db()->table('student_initiative_records')->whereIn('id', $chunk)->update(['is_complete' => true]);
                        }
                    });
                }

                sort($complete);

                return $complete;
            },
        ],
    ];
}

/** The engine's plan for a query, as text. */
function eavSpikeExplain(Builder $query): string
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);
    $prefix = $db->getDriverName() === 'sqlite' ? 'explain query plan ' : 'explain ';

    return collect($db->select($prefix.$query->toSql(), $query->getBindings()))
        ->map(fn ($row) => implode(' | ', array_map(fn ($v) => (string) $v, (array) $row)))
        ->implode("\n");
}

/**
 * Integrity the schema leaves to the application (spike-eav-results.md, open question Q-1): each value
 * sets at most one typed column, and its field belongs to the version its record's initiative uses.
 *
 * @return array{multi_typed: int, foreign_field: int}
 */
function eavSpikeIntegrity(): array
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);

    // Any two typed columns set together — ten pairs, written as conditions rather than raw SQL.
    $columns = ['value_text', 'value_number', 'value_date', 'value_bool', 'value_json'];
    $multiTyped = $db->table('student_initiative_values')->where(function ($q) use ($columns): void {
        foreach ($columns as $i => $a) {
            foreach (array_slice($columns, $i + 1) as $b) {
                $q->orWhere(fn ($pair) => $pair->whereNotNull($a)->whereNotNull($b));
            }
        }
    })->count();

    $foreignField = $db->table('student_initiative_values as v')
        ->join('student_initiative_records as r', 'r.id', '=', 'v.record_id')
        ->join('initiative_sections as a', 'a.id', '=', 'r.assignment_id')
        ->join('initiatives as i', 'i.id', '=', 'a.initiative_id')
        ->join('initiative_fields as f', 'f.id', '=', 'v.field_id')
        ->whereColumn('f.schema_version_id', '<>', 'i.current_schema_version_id')
        ->count();

    return ['multi_typed' => $multiTyped, 'foreign_field' => $foreignField];
}

/**
 * Engine, version and machine, for the results file.
 *
 * @return array<string, string>
 */
function eavSpikeEnvironment(string $engine): array
{
    $db = DB::connection(EAV_SPIKE_CONNECTION);
    $memory = PHP_OS_FAMILY === 'Windows'
        ? trim((string) @shell_exec('powershell -NoProfile -Command "[math]::Round((Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB)"'))
        : trim((string) @shell_exec("awk '/MemTotal/ {printf \"%d\", $2/1048576}' /proc/meminfo"));

    return [
        'engine' => $engine,
        'engine_version' => (string) ($db->selectOne($engine === 'sqlite' ? 'select sqlite_version() as v' : 'select version() as v')->v ?? ''),
        'php' => PHP_VERSION,
        'os' => php_uname('s').' '.php_uname('r'),
        'cpu' => (string) (getenv('PROCESSOR_IDENTIFIER') ?: trim((string) @shell_exec("grep -m1 'model name' /proc/cpuinfo | cut -d: -f2"))),
        'cpu_threads' => (string) (getenv('NUMBER_OF_PROCESSORS') ?: trim((string) @shell_exec('nproc'))),
        'memory_gb' => $memory,
        'cold_command' => (string) config('eav_spike.cold_command', '') ?: 'none — fresh connection per run, OS/engine cache warm',
    ];
}
