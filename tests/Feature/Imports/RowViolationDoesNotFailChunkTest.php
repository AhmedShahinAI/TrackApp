<?php

declare(strict_types=1);

use App\Models\ImportRow;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\ImportReasons;
use App\Modules\Imports\Pipeline\Resolver;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T215 (database half) — a unique violation is a row's error, not its chunk's (FR-066, R-04)
|--------------------------------------------------------------------------
|
| The race the index exists for: someone else enrolls the same student in the same section while
| this row is being committed. The Resolver below plays the other party by writing a second copy of
| the enrollment inside the row's own work, so the unique index fires for real within the row's
| savepoint. The violating row must become an error with a reason, everything that row wrote must
| roll back with it, and every other row in the chunk must still commit.
|
*/

final class LosesRaceOnRow extends Resolver
{
    public static ?int $row = null;

    public function commit(ImportRow $row): array
    {
        if ($row->row_number !== self::$row) {
            return parent::commit($row);
        }

        // Commit as normal, then write the same enrollment again, as a concurrent import would.
        $result = parent::commit($row);

        DB::table('section_enrollments')->insert([
            'section_id' => $result['resolved']['section_id'],
            'student_id' => $result['resolved']['student_id'],
            'status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $result;
    }
}

test('the colliding row is an error with a reason and the rest of its chunk commits', function (): void {
    $world = importWorld();

    LosesRaceOnRow::$row = 4;
    app()->bind(Resolver::class, fn ($app, array $parameters) => new LosesRaceOnRow($parameters['job']));

    $job = importRoster($world, rosterRows($world, 6));

    $collided = ImportRow::query()->where('import_job_id', $job->id)->where('row_number', 4)->firstOrFail();

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(5)
        ->and($job->errors)->toBe(1)
        ->and($collided->status)->toBe('error')
        ->and(array_column($collided->reasons, 'reason_key'))->toContain(ImportReasons::COMMIT_CONFLICT)

        // Five from the import, none for the collided row: its savepoint rolled the pair back.
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->count())->toBe(5);
});

test('a row that rolls back takes the section it created with it, and the next row makes it again', function (): void {
    $world = importWorld();

    // Row 2 is the first row of section 131, so it is the row that creates the section — and then
    // loses it to the rollback. A resolver that remembered the lost section would hand row 3 an id
    // that no longer exists, and the chunk would fail on the foreign key.
    LosesRaceOnRow::$row = 2;
    app()->bind(Resolver::class, fn ($app, array $parameters) => new LosesRaceOnRow($parameters['job']));

    $job = importRoster($world, rosterRows($world, 3));

    expect($job->status)->toBe('completed')
        ->and($job->imported)->toBe(2)
        ->and($job->errors)->toBe(1)
        ->and(App\Models\Section::query()->withoutGlobalScopes()->where('section_number', '131')->count())->toBe(1);
});
