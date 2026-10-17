<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T465 — every icon the sidebar or the concept map names is in the sprite
|--------------------------------------------------------------------------
|
| The sprite (public/icons/lucide.svg) is built from a list in scripts/build-icons.mjs, and a name
| that is in config but not in the built sprite renders an empty <svg>, with no error anywhere. Phase 26
| found exactly that: the support entry's `life-buoy` was in the script's list and config, but not in
| the committed sprite, which had not been rebuilt since.
|
*/

/** @return list<string> */
function spriteSymbols(): array
{
    preg_match_all('/<symbol[^>]*\bid="([^"]+)"/', (string) file_get_contents(public_path('icons/lucide.svg')), $matches);

    return $matches[1];
}

test('every sidebar icon is in the built sprite', function (): void {
    $missing = collect((array) config('navigation.items'))->pluck('icon')->unique()
        ->diff(spriteSymbols())->values()->all();

    expect($missing)->toBe([]);
});

test('every concept icon is in the built sprite', function (): void {
    expect(array_values(array_diff(array_values(config('icons.concepts')), spriteSymbols())))->toBe([]);
});

test('v2\'s new navigation names are translated in both languages, and the undecided ones have no entry', function (): void {
    // T466. The keys are seeded now; `approvals` waits for Phase 27 (C-22) and `myinitiatives` for a
    // student portal that was retired (C-19). An entry appearing before its screen would be a dead link.
    foreach (['approvals', 'myinitiatives', 'studentsadmin', 'support'] as $key) {
        foreach (['ar', 'en'] as $locale) {
            expect(trans("nav.{$key}", [], $locale))->not->toBe("nav.{$key}", "nav.{$key} is missing in {$locale}");
        }
    }

    expect(trans('nav.support', [], 'ar'))->toBe('طلبات الدعم الفني')
        ->and(config('navigation.items'))->not->toHaveKey('myinitiatives');

    // `approvals` was seeded here and is built by Phase 27 (T499), for the two roles that decide.
    // `myinitiatives` stays absent: the student portal it belongs to was retired (C-19).
    foreach (config('navigation.roles') as $role => $entries) {
        expect(in_array('myinitiatives', $entries, true))->toBeFalse("{$role} lists myinitiatives");
    }

    expect(config('navigation.roles.INSTRUCTOR'))->not->toContain('approvals')
        ->and(config('navigation.roles.SUPER_ADMIN'))->toContain('approvals')
        ->and(config('navigation.roles.BRANCH_COORDINATOR'))->toContain('approvals');
});

test('the built sprite is exactly the icon list it is built from, so it cannot be left stale', function (): void {
    /*
     * Phase 26 review: the root cause of the empty support icon was not a missing name but a sprite
     * nobody rebuilt after the name was added to scripts/build-icons.mjs. Comparing the two catches
     * that whichever icon it is, including one added to the list for a view nobody has opened yet.
     */
    $script = (string) file_get_contents(base_path('scripts/build-icons.mjs'));
    $block = (string) preg_replace('#//[^\n]*#', '', substr($script, (int) strpos($script, 'const ICONS'), (int) strpos($script, '};', (int) strpos($script, 'const ICONS')) - (int) strpos($script, 'const ICONS')));
    preg_match_all("/'([a-z0-9-]+)'/", $block, $listed);

    $listed = array_values(array_unique($listed[1]));
    $built = spriteSymbols();
    sort($listed);
    sort($built);

    expect($listed)->not->toBeEmpty()
        ->and($built)->toBe($listed, 'public/icons/lucide.svg is stale: run `npm run build:icons`.');
});

test('every icon named literally in a view or in application code is in the built sprite', function (): void {
    // Phase 26 review: config was checked, but `icon="…"` in Blade and `'icon' => '…'` in PHP were not.
    $pattern = '/(?:(?<![\w:-])(?:icon|empty-icon)="([a-z0-9-]+)"|<x-track-icon[^>]*?\bname="([a-z0-9-]+)"|\'(?:icon|empty_icon)\'\s*=>\s*\'([a-z0-9-]+)\')/';
    $named = [];

    foreach (Symfony\Component\Finder\Finder::create()->files()->in([resource_path('views'), app_path()])->name(['*.blade.php', '*.php']) as $file) {
        preg_match_all($pattern, $file->getContents(), $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $named[(string) (array_values(array_filter(array_slice($match, 1)))[0] ?? '')][] = $file->getRelativePathname();
        }
    }

    // The control: the scan finds the icons it is meant to (the students list's empty state, T468).
    expect($named)->toHaveKey('graduation-cap')
        ->and(array_keys(array_diff_key($named, array_flip(spriteSymbols()))))->toBe([]);
});
