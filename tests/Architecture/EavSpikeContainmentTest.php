<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T291 — the Group 6 tables exist for the spike only: nothing in this feature serves them
|--------------------------------------------------------------------------
|
| plan.md Complexity Tracking: the typed EAV tables are created in this feature so the capacity spike
| measures the real schema, and on that condition — no model beyond what the seeder needs, no route,
| no controller, no policy, no view. Initiatives are Phase 2's; a screen that reached these tables
| now would be building Phase 2 on an unmeasured, unapproved design.
|
| What counts as a reference: any of the six Group 6 table names, `initiatives` used as a table
| (`table('initiatives')`, a `$table` property, a join), or an `Initiative*` model class. The
| navigation stub "initiatives" (a disabled menu item) and a placeholder array key are not table
| access and do not match.
*/

/** @return list<string> */
function eavSpikeGroup6Patterns(): array
{
    return [
        '/\b(initiative_schema_versions|initiative_fields|initiative_sections|initiative_targets|student_initiative_records|student_initiative_values)\b/',
        '/(table|from|join|leftJoin|rightJoin)\s*\(\s*[\'"]initiatives[\'"]/',
        // Raw SQL: `select … from initiatives`, `join initiatives`, `insert into initiatives`, `update initiatives`.
        '/\b(from|join|into|update)\s+[`"]?initiatives\b/i',
        '/\$table\s*=\s*[\'"]initiatives[\'"]/',
        '/\bclass\s+(Initiative\w*|StudentInitiative\w*)\b/',
        '/\b(Models\\\\)?(Initiative|InitiativeField|InitiativeSection|StudentInitiativeRecord|StudentInitiativeValue)::/',
    ];
}

/**
 * Every file the running application loads that mentions a Group 6 table: code, routes, views,
 * config, bootstrap and the browser bundle's sources. database/ (migrations, the seeder) and tests/
 * hold the spike itself, by design; config/eav_spike.php only names the switch.
 *
 * @return list<string>
 */
function eavSpikeReferences(): array
{
    $finder = Finder::create()->files()
        ->in([app_path(), base_path('routes'), resource_path('views'), config_path(), base_path('bootstrap'), resource_path('js')])
        ->exclude('cache')
        ->notName('eav_spike.php')
        ->name(['*.php', '*.js']);
    $hits = [];

    foreach ($finder as $file) {
        $contents = (string) file_get_contents((string) $file->getRealPath());

        foreach (eavSpikeGroup6Patterns() as $pattern) {
            if (preg_match($pattern, $contents, $match) === 1) {
                $hits[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', (string) $file->getRealPath()).': '.$match[0];
            }
        }
    }

    return $hits;
}

test('no controller, policy, model, action, route or view references the Group 6 tables', function (): void {
    expect(eavSpikeReferences())->toBe([]);
});

test('no registered route reaches an initiative endpoint', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => preg_match('/initiative/i', $route->uri().' '.(string) $route->getName()) === 1)
        ->map(fn ($route) => $route->uri())
        ->values()
        ->all();

    expect($routes)->toBe([]);
});

test('the guard would notice a reference', function (): void {
    // The patterns are what the containment rests on; a pattern that matches nothing proves nothing.
    $samples = [
        "DB::table('student_initiative_values')->get();",
        "->join('initiatives', 'initiatives.id', '=', 'x.initiative_id')",
        "protected \$table = 'initiatives';",
        "DB::select('select count(*) from initiatives where term_id = ?', [\$term]);",
        'UPDATE initiatives SET status = 1',
        'final class InitiativeController',
        'App\\Models\\Initiative::query()',
    ];

    foreach ($samples as $sample) {
        $matched = collect(eavSpikeGroup6Patterns())->contains(fn ($p) => preg_match($p, $sample) === 1);
        expect($matched)->toBeTrue($sample);
    }

    // And the existing harmless mentions do not trip it.
    foreach (["'initiatives' => ['icon' => 'lightbulb']", "'initiatives' => []"] as $harmless) {
        expect(collect(eavSpikeGroup6Patterns())->contains(fn ($p) => preg_match($p, $harmless) === 1))->toBeFalse($harmless);
    }
});
