<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T052 — no institution-specific literal in application code
|--------------------------------------------------------------------------
|
| Constitution IV. The platform ships to one college today, and the fastest way to make the second
| one impossible is to write "Zulfi" into a conditional. Branch names, campus names, and gender
| values are data; application code reads them from the database.
|
| `app/` only. Seeders and factories under `database/` hold real campus names on purpose — a fixture
| that reproduces the design reference has to name the campuses the reference names.
|
*/

/**
 * @return list<string>
 */
function architectureAppFiles(): array
{
    $files = [];

    foreach (Finder::create()->files()->in(base_path('app'))->name('*.php') as $file) {
        $files[] = $file->getRealPath();
    }

    return $files;
}

test('no campus or branch name appears in app/', function (): void {
    // The three campuses the college actually runs, in both scripts. If one of these is in app/, a
    // conditional somewhere is branching on an institution's current shape.
    $forbidden = ['Zulfi', 'Majmaah', 'Rumah', 'الزلفي', 'المجمعة', 'رماح'];

    $offenders = [];

    foreach (architectureAppFiles() as $path) {
        $contents = (string) file_get_contents($path);

        foreach ($forbidden as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)." contains '{$needle}'";
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('no gender value is compared as a literal in app/', function (): void {
    // `gender` lives in one column (FR-050). Code that compares it to 'male' has made a second,
    // unsynchronised copy of the institution's structure. Reading the value and rendering it is
    // fine; branching on it is what this forbids.
    $pattern = "/(===?|!==?|in_array|match)\\s*\\(?\\s*[^;\\n]{0,40}['\\\"](male|female)['\\\"]/i";

    $offenders = [];

    foreach (architectureAppFiles() as $path) {
        $contents = (string) file_get_contents($path);

        if (preg_match($pattern, $contents) === 1) {
            $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
        }
    }

    expect($offenders)->toBe([]);
});

test('the guard actually fires', function (): void {
    // A scanner that finds nothing is indistinguishable from one that scans nothing. This asserts
    // the detection works by running it against a string that must be caught.
    $pattern = "/(===?|!==?|in_array|match)\\s*\\(?\\s*[^;\\n]{0,40}['\\\"](male|female)['\\\"]/i";

    expect(preg_match($pattern, "if (\$branch->gender === 'male') {"))->toBe(1)
        ->and(preg_match($pattern, '$label = __("gender.".$branch->gender);'))->toBe(0);

    expect(architectureAppFiles())->not->toBeEmpty();
});
