<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T056 — nothing is ever destroyed
|--------------------------------------------------------------------------
|
| Constitution V and FR-049. A student's record, an account, a campus, and a term are all
| historical: a past term stays true after this term changes, which is only possible if the rows
| behind it survive. Deactivation and soft deletion are the removal paths; a hard delete is not.
|
| Two independent assertions, because either alone would be a convention. No `forceDelete()` call in
| application code, and no destroy route for the four entities that carry history.
|
*/

test('no forceDelete call exists in app/', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app'))->name('*.php') as $file) {
        $contents = (string) file_get_contents((string) $file->getRealPath());

        // Matches a call — `->forceDelete(` or `::forceDelete(` — and deliberately not a
        // declaration. AuditLog declares the method in order to throw from it, which is the
        // opposite of the problem this guards against.
        if (preg_match('/(->|::)\s*forceDelete\s*\(/', $contents) === 1) {
            $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', (string) $file->getRealPath());
        }
    }

    expect($offenders)->toBe([]);
});

test('no DELETE route exists for an entity that carries history', function (): void {
    // FR-049. A DELETE verb on any of these is the wrong answer to "remove this": the right answer
    // is deactivation, which keeps every audit entry and every enrollment pointing at a real row.
    $protected = ['users', 'students', 'branches', 'academic-terms', 'terms'];

    $offenders = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! in_array('DELETE', $route->methods(), true)) {
            continue;
        }

        foreach ($protected as $segment) {
            if (str_contains($route->uri(), $segment)) {
                $offenders[] = $route->uri();
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the audit log refuses every mutation path', function (): void {
    // The second immutability layer from research.md R-02, asserted here rather than only in a
    // feature test: these four overrides are what stop application code rewriting history, and a
    // refactor that dropped one would otherwise pass silently until production.
    $log = new App\Models\AuditLog;

    expect(fn (): bool => $log->save())->toThrow(LogicException::class)
        ->and(fn (): bool => $log->update(['action' => 'tampered']))->toThrow(LogicException::class)
        ->and(fn (): bool => $log->delete())->toThrow(LogicException::class)
        ->and(fn (): bool => $log->forceDelete())->toThrow(LogicException::class);
});

test('the forceDelete scanner distinguishes a call from a declaration', function (): void {
    $pattern = '/(->|::)\s*forceDelete\s*\(/';

    expect(preg_match($pattern, '$user->forceDelete();'))->toBe(1)
        ->and(preg_match($pattern, 'Model::forceDelete();'))->toBe(1)
        ->and(preg_match($pattern, 'public function forceDelete(): bool'))->toBe(0);
});
