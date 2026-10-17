<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T456 — the public surface is these routes and no more (constitution "Public endpoints")
|--------------------------------------------------------------------------
|
| PublicSurfaceTest proves every unauthenticated route sits on the `public` group or is a sign-in or
| infrastructure route 001 shipped. This one pins what the `public` group holds. A route added to
| routes/public.php fails here even when it is throttled, padded and correctly placed: a new public
| endpoint needs a constitution amendment, not a pull request.
|
| contracts/support-public.md has five entries. The status page has two routes under its one entry, the
| form and the lookup it posts (Phase 22), so the group holds six routes for five contract entries. Each
| is listed with the entry that documents it.
|
*/

/**
 * @return array<string, string> "METHOD uri" => the contracts/support-public.md entry
 */
function declaredPublicRoutes(): array
{
    return [
        'GET support' => 'GET /support',
        'GET support/challenge' => 'GET /support/challenge',
        'POST support' => 'POST /support',
        'GET support/submitted' => 'GET /support/submitted',
        'GET support/status' => 'POST /support/status (the form it posts from)',
        'POST support/status' => 'POST /support/status',
    ];
}

/** @return list<string> */
function registeredPublicRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! in_array('public', (array) $route->middleware(), true)) {
            continue;
        }

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $routes[] = $method.' '.$route->uri();
        }
    }

    sort($routes);

    return $routes;
}

test('the public group holds exactly the declared routes', function (): void {
    $declared = array_keys(declaredPublicRoutes());
    sort($declared);

    expect(registeredPublicRoutes())->toBe($declared);
});

test('every declared route is documented in the public contract', function (): void {
    $contract = (string) file_get_contents(base_path('specs/001-foundation-access-roster/contracts/support-public.md'));

    foreach (array_unique(array_values(declaredPublicRoutes())) as $entry) {
        $heading = '## '.explode(' (', $entry)[0];

        expect(str_contains($contract, $heading."\n"))->toBeTrue("contracts/support-public.md has no entry {$heading}");
    }
});

test('the bound catches a sixth route', function (): void {
    Route::middleware('public')->get('/support/export', fn () => 'x');
    Route::getRoutes()->refreshNameLookups();

    expect(registeredPublicRoutes())->toContain('GET support/export')
        ->and(array_key_exists('GET support/export', declaredPublicRoutes()))->toBeFalse();
});
