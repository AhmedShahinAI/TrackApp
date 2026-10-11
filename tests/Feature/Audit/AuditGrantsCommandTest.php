<?php

declare(strict_types=1);

use App\Console\Commands\AuditGrants;
use Illuminate\Database\QueryException;

/*
|--------------------------------------------------------------------------
| track:audit-grants — the audit log is append-only for the application's own database user (R-03)
|--------------------------------------------------------------------------
|
| The real check needs MySQL with the revoke applied (staging, production). These pin the logic:
| a refusal (1142) on both statements is a pass, either one succeeding is a failure, and any other
| database error is not mistaken for a refusal.
*/

function deniedException(): QueryException
{
    $pdo = new PDOException('UPDATE command denied to user', 42000);
    $pdo->errorInfo = ['42000', 1142, 'UPDATE command denied to user'];

    return new QueryException('mysql', 'update audit_logs', [], $pdo);
}

test('both statements refused: append-only', function (): void {
    $connection = (fn () => throw deniedException());

    expect(AuditGrants::mutationsAllowed($connection))->toBe([]);
});

test('either statement allowed: reported by name', function (): void {
    $connection = (fn (string $sql) => str_starts_with($sql, 'delete') ? true : throw deniedException());

    expect(AuditGrants::mutationsAllowed($connection))->toBe(['DELETE']);

    $open = (fn () => true);

    expect(AuditGrants::mutationsAllowed($open))->toBe(['UPDATE', 'DELETE']);
});

test('the probes can never change a row', function (): void {
    $seen = [];
    $connection = (function (string $sql) use (&$seen): void {
        $seen[] = $sql;

        throw deniedException();
    });

    AuditGrants::mutationsAllowed($connection);

    expect($seen)->toHaveCount(2);

    foreach ($seen as $sql) {
        expect($sql)->toEndWith('where 1 = 0');
    }
});

test('another database error is not mistaken for a refusal', function (): void {
    $pdo = new PDOException('Table missing', 42000);
    $pdo->errorInfo = ['42S02', 1146, "Table 'audit_logs' doesn't exist"];
    $connection = (fn () => throw new QueryException('mysql', 'update', [], $pdo));

    expect(fn () => AuditGrants::mutationsAllowed($connection))->toThrow(QueryException::class);
});

test('on a database without grants it says so, and fails only when told to be strict', function (): void {
    $this->artisan('track:audit-grants')->expectsOutputToContain('Not applicable')->assertSuccessful();
    $this->artisan('track:audit-grants', ['--strict' => true])->assertFailed();
});
