<?php

declare(strict_types=1);

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| T155 — three independent layers, asserted independently (FR-031)
|--------------------------------------------------------------------------
|
| contracts/audit.md: "A test asserts each layer independently, because any one of them alone would
| be a convention rather than a guarantee."
|
|   1. No update or delete path in code — the model refuses every mutating entry point Eloquent
|      offers, so a developer reaching for one gets an immediate, explanatory failure.
|   2. No `updated_at` column — there is nowhere to record a change, so a change cannot be made to
|      look legitimate.
|   3. The database user holds no UPDATE or DELETE on the table — the only layer that survives a
|      developer who writes raw SQL.
|
| Layer 3 is the one that cannot be asserted here. It is a MySQL grant, and the suite runs on
| SQLite, which has no per-table privileges at all. The test says so out loud and skips rather than
| asserting something weaker and calling it the same thing.
|
*/

function anAuditRow(): int
{
    return (int) DB::table('audit_logs')->insertGetId([
        'user_id' => null,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);
}

test('layer 1 — every mutating method on the model throws', function (string $method, array $arguments): void {
    anAuditRow();

    $entry = AuditLog::query()->first();
    expect($entry)->not->toBeNull();

    expect(fn () => $entry->{$method}(...$arguments))->toThrow(LogicException::class);
})->with([
    'save' => ['save', [[]]],
    'update' => ['update', [['action' => 'tampered']]],
    'delete' => ['delete', []],
    'forceDelete' => ['forceDelete', []],
]);

test('layer 1 — mass assignment is refused outright', function (): void {
    $entry = new AuditLog;

    /*
     * $guarded = ['*'] with Laravel's strict mass assignment turned on, so fill() does not quietly
     * drop the attributes — it throws. That is stronger than the silent-ignore this test first
     * asserted, and it is the behaviour worth pinning: a developer who tries gets told.
     */
    expect(fn () => $entry->fill(['action' => 'tampered', 'user_id' => 99]))
        ->toThrow(Illuminate\Database\Eloquent\MassAssignmentException::class);

    expect($entry->getAttributes())->toBe([]);
});

test('layer 2 — the table has no updated_at column', function (): void {
    $columns = Schema::getColumnListing('audit_logs');

    expect($columns)->not->toContain('updated_at')
        ->and($columns)->toContain('created_at');

    // And the model knows it, so Eloquent never tries to write one.
    expect(AuditLog::UPDATED_AT)->toBeNull();
});

test('layer 2 — a created entry carries a timestamp it cannot later contradict', function (): void {
    $id = anAuditRow();

    $row = (array) DB::table('audit_logs')->find($id);

    expect($row['created_at'])->not->toBeNull();
    expect($row)->not->toHaveKey('updated_at');
});

test('layer 3 — the database refuses UPDATE and DELETE for the application user', function (): void {
    $driver = DB::connection()->getDriverName();

    /*
     * Stated, not silently passed. SQLite has no GRANT system, so the privilege layer simply does
     * not exist here — and a test that quietly succeeded would report three layers verified while
     * having checked two. The migration that revokes the grants is 000016; what it does on MySQL 8
     * remains unverified until this suite runs against one.
     */
    if ($driver !== 'mysql') {
        $this->markTestSkipped(
            "Layer 3 is a MySQL grant. The suite runs on '{$driver}', which has no per-table ".
            'privileges, so this layer is unverified here rather than verified weakly.',
        );
    }

    anAuditRow();

    expect(fn () => DB::statement("UPDATE audit_logs SET action = 'tampered'"))
        ->toThrow(Illuminate\Database\QueryException::class);

    expect(fn () => DB::statement('DELETE FROM audit_logs'))
        ->toThrow(Illuminate\Database\QueryException::class);
});

test('there is no route that mutates the audit log', function (): void {
    $mutating = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'audit'))
        ->reject(fn ($route) => $route->methods() === ['GET', 'HEAD'])
        ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    // contracts/audit.md, "Absent by design": no POST, PUT, PATCH or DELETE on the audit log.
    expect($mutating)->toBe([]);
});

test('no console command mutates the audit log', function (): void {
    $offenders = [];

    foreach (glob(app_path('Console/Commands/*.php')) ?: [] as $file) {
        $source = (string) file_get_contents($file);

        if (preg_match('/audit_logs/', $source) && preg_match('/->(update|delete|truncate)\(/', $source)) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});
