<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\TargetableSection;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T480 — a section that is not approved can never be targeted (FR-174)
|--------------------------------------------------------------------------
|
| The most consequential rule of Amendment 4: a pending section leaking into an initiative would
| corrupt every completion figure computed from it.
|
| Initiative targeting is not built in this release; there is no preview, assignment or denominator
| to test yet. What exists is the one query all three must use, TargetableSection, whose global scope
| admits approved sections only, and a guard that no application code reaches the initiative tables
| any other way.
|
*/

test('only an approved section is targetable; pending, returned and rejected never are', function (): void {
    $world = approvalWorld();

    $active = sectionCreatedBy($world['admin'], $world);
    $pending = sectionCreatedBy($world['instructor'], $world);
    $returned = sectionCreatedBy($world['instructor'], $world);
    $rejected = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $returned, 'return', 'Wrong programme for this course.')->assertOk();
    decideSection($world['coordinator'], $rejected, 'reject', 'This section duplicates another one.')->assertOk();

    app('auth')->forgetGuards();
    $targetable = TargetableSection::query()->withoutGlobalScope(App\Modules\Authorization\Scopes\BranchScope::class)->pluck('id')->all();

    expect($targetable)->toContain($active->id);
    expect(array_intersect($targetable, [$pending->id, $returned->id, $rejected->id]))->toBe([]);
});

test('the active-only rule survives a caller who forgets it', function (): void {
    $world = approvalWorld();
    $pending = sectionCreatedBy($world['instructor'], $world);

    // A later endpoint asking plainly for "all sections with this id" still cannot get a pending one.
    expect(TargetableSection::query()->withoutGlobalScope(App\Modules\Authorization\Scopes\BranchScope::class)->whereKey($pending->id)->exists())->toBeFalse()
        ->and(TargetableSection::query()->withoutGlobalScope(App\Modules\Authorization\Scopes\BranchScope::class)->where('status', 'pending')->count())->toBe(0);
});

test('a section approved later becomes targetable', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $section, 'approve')->assertOk();

    app('auth')->forgetGuards();
    expect(TargetableSection::query()->withoutGlobalScope(App\Modules\Authorization\Scopes\BranchScope::class)->whereKey($section->id)->exists())->toBeTrue();
});

test('the targeting view of a section is read-only', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['admin'], $world);

    $targetable = TargetableSection::query()->withoutGlobalScopes()->findOrFail($section->id);

    expect(fn () => $targetable->forceFill(['status' => 'pending'])->save())->toThrow(LogicException::class)
        ->and(Section::query()->withoutGlobalScopes()->findOrFail($section->id)->status)->toBe('active');
});

test('no application code reaches the initiative tables except through TargetableSection', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in([app_path(), base_path('routes'), resource_path('views')])->name('*.php') as $file) {
        if ($file->getRealPath() === realpath(app_path('Models/TargetableSection.php'))) {
            continue;
        }

        // The bare word anywhere (Phase 27 review): raw SQL, a concatenation's halves, a variable's value.
        // `initiative_` split across a concatenation is caught by the second pattern.
        $code = $file->getContents();

        if (preg_match('/\binitiative_(sections|targets)\b/', $code) === 1 || preg_match('/[\'"]initiative_[\'"]\s*\./', $code) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
