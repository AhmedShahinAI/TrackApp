<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T030 - the engine-level half of the append-only guarantee (research.md R-02, constitution II).
 *
 * Layers one and two - no updated_at column, no update path in code - make mutation unreachable from
 * the application as written. This one makes it unreachable from an application that has been
 * changed or compromised: the database user simply does not hold UPDATE or DELETE on the table.
 *
 * Guarded deliberately. A local developer database and a CI container both run as a user without
 * GRANT OPTION, and failing the migration there would make the schema unrunnable in exactly the two
 * places it is run most. The Pest companion asserts the grant state in staging and production, so a
 * no-op here cannot pass for a success where it matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::connection()->getDatabaseName();
        $user = (string) DB::connection()->getConfig('username');

        if ($database === '' || $user === '') {
            return;
        }

        try {
            DB::statement(sprintf(
                'REVOKE UPDATE, DELETE ON `%s`.`audit_logs` FROM %s',
                str_replace('`', '', $database),
                self::quoteGrantee($user),
            ));
        } catch (Throwable $e) {
            // No GRANT OPTION, or no such grant to revoke. Both are expected locally and in CI.
            // Swallowing here is safe because it is not the only thing standing between the
            // application and a rewritten audit row.
        }
    }

    public function down(): void
    {
        // Deliberately not reversible. Restoring UPDATE and DELETE on the audit log is a privilege
        // decision for a database administrator, not something a rollback should hand back silently.
    }

    /**
     * Render a grantee as MySQL wants it, without interpolating the name into the statement raw.
     */
    private static function quoteGrantee(string $user): string
    {
        [$name, $host] = str_contains($user, '@')
            ? explode('@', $user, 2)
            : [$user, '%'];

        return sprintf("'%s'@'%s'", str_replace("'", '', $name), str_replace("'", '', $host));
    }
};
