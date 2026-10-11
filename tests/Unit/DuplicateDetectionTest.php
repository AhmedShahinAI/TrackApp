<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T215 — duplicate detection at four levels, each enforced by a unique index (FR-066, R-04)
|--------------------------------------------------------------------------
|
| Read from the migrations, not from a live schema, because this suite deliberately has no booted
| framework (see tests/Pest.php). The claim is about the schema's definition — "the index exists,
| so the application check is only for the readable reason" — and the definition is the migration.
| That the index then turns a violating row into a row-level error without failing its chunk needs
| a database and lives in tests/Feature/Imports/RowViolationDoesNotFailChunkTest.php.
|
| The fourth level, student + assignment, is the Phase 12 schema (T287): `initiative_targets`, now
| created. It is also proven against the table itself in
| tests/Feature/EavSpike/SpikeToolingSafetyTest.php ("level 4 — … at the database").
|
*/

function migrationSource(string $table): ?string
{
    foreach (glob(__DIR__.'/../../database/migrations/*.php') ?: [] as $file) {
        $source = (string) file_get_contents($file);

        if (preg_match("/Schema::create\\('".preg_quote($table, '/')."'/", $source) === 1) {
            return $source;
        }
    }

    return null;
}

test('level 1 — a University ID is unique', function (): void {
    expect(migrationSource('students'))->toMatch("/string\\('university_id', 20\\)->unique\\(\\)/");
});

test('level 2 — the section key is unique among live sections', function (): void {
    $source = (string) migrationSource('sections');

    // approval_key is section_number for every section that is not rejected (T471), so this is the
    // (term, branch, course, number) key with rejected sections releasing their number.
    expect($source)->toContain("\$table->unique(['term_id', 'branch_id', 'course_id', 'approval_key'])")
        ->and($source)->toContain("case when status = 'rejected' then null else section_number end");
});

test('level 3 — a student is enrolled in a section once', function (): void {
    expect(migrationSource('section_enrollments'))->toContain("\$table->unique(['student_id', 'section_id'])");
});

test('level 4 — a student is targeted by an assignment once', function (): void {
    expect(migrationSource('initiative_targets'))->toContain("unique(['assignment_id', 'student_id'])");
});
