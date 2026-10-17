<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T458 — a student is a roster record, never an account (Amendment 2's central constraint)
|--------------------------------------------------------------------------
|
| The support channel verifies a student by their University ID against the roster and gives them
| nothing to sign in with. That is why feature 001 needed no change, and it holds only while `students`
| has no credential, contact or activation column. The table is pinned to its exact columns, so a new
| one, whatever its name, needs this test changed beside it, and the names that would make a student
| an account are refused outright.
|
*/

uses(RefreshDatabase::class);

test('students carries exactly its roster columns', function (): void {
    expect(Schema::getColumnListing('students'))->toEqualCanonicalizing([
        'id', 'university_id', 'name_ar', 'name_en', 'created_at', 'updated_at', 'deleted_at',
    ]);
});

test('no student column is a credential, a contact or an activation', function (): void {
    $forbidden = '/password|token|secret|otp|pin|email|phone|mobile|contact|activat|verif|remember|login/i';

    $offenders = array_values(array_filter(
        Schema::getColumnListing('students'),
        fn (string $column): bool => preg_match($forbidden, $column) === 1,
    ));

    expect($offenders)->toBe([]);
});

test('no migration after the students table alters it', function (): void {
    $offenders = [];

    foreach ((new Finder)->files()->in(database_path('migrations'))->name('*.php') as $file) {
        if (preg_match("/Schema::table\(\s*['\"]students['\"]/", $file->getContents()) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});

test('no table linked to students carries a credential either', function (): void {
    /*
     * Phase 25 review, L-4. Pinning `students` alone would let an account arrive beside it: a
     * `student_accounts` table holding a password and a student_id is the same account under another
     * name. Every table with a foreign key to students is checked for credential columns.
     *
     * `contact` is not on this list: support_requests holds the contact a student typed, unverified and
     * cleared by retention, by design (FR-145, R-12). Nothing authenticates with it.
     */
    $credential = '/password|token|secret|otp|remember|activat|login|credential|passcode/i';
    $linked = [];
    $offenders = [];

    foreach (Schema::getTableListing() as $table) {
        $table = str_contains($table, '.') ? substr($table, strpos($table, '.') + 1) : $table;

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['foreign_table'] !== 'students') {
                continue;
            }

            $linked[] = $table;

            foreach (Schema::getColumnListing($table) as $column) {
                if (preg_match($credential, $column) === 1) {
                    $offenders[] = "{$table}.{$column}";
                }
            }
        }
    }

    // The scan reaches the tables it is meant to: the control that it is not passing over nothing.
    expect($linked)->toContain('section_enrollments', 'support_requests')
        ->and($offenders)->toBe([]);
});
