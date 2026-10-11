<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\ImportJob;
use App\Modules\Imports\Pipeline\ResolveBranchFromCampus;

/*
|--------------------------------------------------------------------------
| T323 — «المجمعة (طلاب)» to exactly one branch, or the file is refused (FR-099)
|--------------------------------------------------------------------------
*/

test('the campus value splits into campus and gender, in either bracket style', function (string $value, string $campus, ?string $gender): void {
    expect(ResolveBranchFromCampus::split($value))->toBe([$campus, $gender]);
})->with([
    ['المجمعة (طلاب)', 'المجمعة', 'male'],
    ['المجمعة (طالبات)', 'المجمعة', 'female'],
    ['الزلفي（طلاب）', 'الزلفي', 'male'],
    ['المجمعة', 'المجمعة', null],
    // Brackets that are not a gender belong to the name.
    ['المجمعة (المبنى الجديد)', 'المجمعة (المبنى الجديد)', null],
]);

test('«المجمعة (طلاب)» resolves to exactly the male Majmaah branch, not its female counterpart', function (): void {
    $world = registrarWorld();
    Branch::factory()->create(['organization_id' => $world['branch']->organization_id, 'campus_name_ar' => 'المجمعة', 'gender' => 'female']);

    $result = (new ResolveBranchFromCampus)->resolve('المجمعة (طلاب)', (int) $world['branch']->organization_id);

    expect($result)->toMatchArray(['status' => ResolveBranchFromCampus::RESOLVED, 'branch_id' => $world['branch']->id]);
});

test('no match, an inactive branch, and another organization\'s branch all resolve to none', function (): void {
    $world = registrarWorld();
    $organizationId = (int) $world['branch']->organization_id;
    Branch::factory()->create(['organization_id' => $organizationId, 'campus_name_ar' => 'شقراء', 'gender' => 'male', 'is_active' => false]);
    Branch::factory()->create(['organization_id' => App\Models\Organization::factory()->create()->id, 'campus_name_ar' => 'حوطة سدير', 'gender' => 'male']);

    $resolver = new ResolveBranchFromCampus;

    expect($resolver->resolve('الرياض (طلاب)', $organizationId)['status'])->toBe(ResolveBranchFromCampus::NONE)
        ->and($resolver->resolve('شقراء (طلاب)', $organizationId)['status'])->toBe(ResolveBranchFromCampus::NONE)
        ->and($resolver->resolve('حوطة سدير (طلاب)', $organizationId)['status'])->toBe(ResolveBranchFromCampus::NONE);
});

test('a campus with no gender matching both of its branches is ambiguous, never guessed', function (): void {
    $world = registrarWorld();
    Branch::factory()->create(['organization_id' => $world['branch']->organization_id, 'campus_name_ar' => 'المجمعة', 'gender' => 'female']);

    expect((new ResolveBranchFromCampus)->resolve('المجمعة', (int) $world['branch']->organization_id))
        ->toMatchArray(['status' => ResolveBranchFromCampus::AMBIGUOUS, 'branch_id' => null]);
});

test('the upload refuses a campus resolving to none or to several, naming it, before any row is processed', function (array $block, string $key): void {
    $world = registrarWorld();
    Branch::factory()->create(['organization_id' => $world['branch']->organization_id, 'campus_name_ar' => 'المجمعة', 'gender' => 'female']);

    $response = postRegistrar($world, registrarFile($block + REGISTRAR_BLOCK, registrarRows(5)))->assertUnprocessable();

    expect($response->json('errors.file.0'))->toBe(__($key, ['value' => $block['المقر']]))
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(App\Models\ImportRow::query()->count())->toBe(0);
})->with([
    'none' => [['المقر' => 'الرياض (طلاب)'], 'imports.registrar.campus_unknown'],
    'several' => [['المقر' => 'المجمعة'], 'imports.registrar.campus_ambiguous'],
]);
