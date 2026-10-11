<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\Student;
use Database\Seeders\EavSpikeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Phase 12 review — the spike's tooling cannot touch application data, and level-4 duplicates are refused
|--------------------------------------------------------------------------
|
| The spike drops, migrates and reseeds the database it runs on, so where it runs is the safety
| property: never the application's database, never a MySQL system schema, never production.
*/

test('the spike refuses every application or system schema, however it is written', function (): void {
    config([
        'database.connections.mysql.database' => 'track',
        'database.connections.mariadb.database' => 'track_reporting',
    ]);

    foreach (['track', 'TRACK', ' Track ', '`track`', 'track_reporting', 'mysql', 'SYS', 'information_schema', 'performance_schema', '', '``'] as $name) {
        expect(eavSpikeSchemaIsForbidden($name))->toBeTrue("\"{$name}\" should be refused");
    }

    expect(eavSpikeSchemaIsForbidden('track_eav_spike'))->toBeFalse();
});

test('the spike stops before touching the server when the schema is forbidden', function (): void {
    config(['database.connections.mysql.database' => 'track', 'eav_spike.mysql_database' => 'Track']);

    expect(fn () => eavSpikeDatabase())->toThrow(RuntimeException::class, 'application or system schema');
});

test('the seeder refuses any connection but the spike\'s own', function (): void {
    expect(DB::getDefaultConnection())->not->toBe(EavSpikeSeeder::CONNECTION)
        ->and(fn () => (new EavSpikeSeeder)->run())->toThrow(RuntimeException::class, 'only to the eav_spike connection');

    // And nothing was written on the way to refusing.
    expect(DB::table('sections')->count())->toBe(0);
});

test('the seeder refuses to run in production, whatever the connection', function (): void {
    app()->detectEnvironment(fn () => 'production');

    try {
        expect(fn () => (new EavSpikeSeeder)->run())->toThrow(RuntimeException::class, 'does not run in production');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

test('the application\'s migrations do not create the indexes under measurement', function (): void {
    // Whether they ship is the spike's decision, pending the MySQL run (spike-eav-results.md).
    expect(DB::getSchemaBuilder()->hasIndex('student_initiative_values', 'siv_field_bool_index'))->toBeFalse()
        ->and(DB::getSchemaBuilder()->hasIndex('student_initiative_records', 'sir_assignment_complete_index'))->toBeFalse();
});

test('level 4 — a student is targeted by an assignment once, at the database', function (): void {
    // T215's fourth duplicate level (R-04), against the table rather than the migration's text.
    $section = Section::factory()->create();
    $student = Student::factory()->create();
    $now = now();

    $initiative = DB::table('initiatives')->insertGetId(['term_id' => $section->term_id, 'name_ar' => 'م', 'name_en' => 'I', 'created_at' => $now, 'updated_at' => $now]);
    $assignment = DB::table('initiative_sections')->insertGetId(['initiative_id' => $initiative, 'section_id' => $section->id, 'created_at' => $now, 'updated_at' => $now]);
    $target = ['assignment_id' => $assignment, 'student_id' => $student->id, 'created_at' => $now, 'updated_at' => $now];

    DB::table('initiative_targets')->insert($target);

    expect(fn () => DB::table('initiative_targets')->insert($target))->toThrow(QueryException::class);
});
