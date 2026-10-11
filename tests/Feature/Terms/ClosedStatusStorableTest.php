<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T173 — `closed` and `archived` are storable, and nothing here sets `closed`
|--------------------------------------------------------------------------
|
| plan.md's Complexity Tracking draws a line: the two statuses exist in the enum and can be set
| through the status endpoint, but the write-blocking they imply — refusing enrollment changes in a
| closed term — is a later feature. Shipping the enum without the enforcement is a deliberate,
| recorded decision, and this test is what keeps it deliberate.
|
| So it asserts two different things: the column accepts both values, and no seeder or screen in
| this feature puts a term into `closed` on its own.
|
*/

test('the enum accepts closed and archived', function (string $status): void {
    $term = AcademicTerm::factory()->create(['status' => $status]);

    expect($term->fresh()->status)->toBe($status);

    expect(DB::table('academic_terms')->where('id', $term->id)->value('status'))->toBe($status);
})->with(['closed', 'archived']);

test('the application seeders put no term into closed', function (): void {
    $this->seed();

    /*
     * This is what data-model.md's "no seeder sets `closed` in this feature" protects: the
     * application must not ship data that implies the closure *enforcement* exists. Write-blocking
     * in a closed term is a later feature (plan.md Complexity Tracking), and a seeded closed term
     * would make the first real closure look like a regression rather than a new capability.
     */
    expect(AcademicTerm::query()->where('status', 'closed')->count())->toBe(0);
});

test('the UI fixture has exactly one closed term, and it is the documented exception', function (): void {
    $this->seed();
    $this->seed(Database\Seeders\UiFixtureSeeder::class);

    /*
     * UiFixtureSeeder is not an application seeder. It exists to give the visual baselines a
     * dataset, and the terms screen has a `closed` badge that cannot be photographed without a
     * closed term. The exception is pinned at exactly one so it cannot quietly grow into a fixture
     * that behaves as though closure were implemented.
     *
     * Raised with the Product Owner rather than resolved here: T173 says "no seeder", the fixture
     * says "this one, for the badge", and both are reasonable readings of the same note.
     */
    $closed = AcademicTerm::query()->where('status', 'closed')->get();

    expect($closed)->toHaveCount(1)
        ->and($closed->first()->id)->toBe(2)
        ->and($closed->first()->is_current)->toBeFalse();
});

test('no screen in this feature offers a direct jump to closed', function (): void {
    $offenders = [];

    foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $view) {
        $source = (string) file_get_contents($view);

        // A hard-coded `closed` in a status control would be a screen setting it without the
        // state machine's knowledge.
        if (preg_match("/value=[\"']closed[\"']/", $source)) {
            $offenders[] = basename($view);
        }
    }

    expect($offenders)->toBe([]);
});
