<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * `track:audit-grants` — is the audit log append-only *for this database user*? (security review R-03)
 *
 * The REVOKE migration (2026_01_01_000016) silently does nothing when the migrating user lacks GRANT
 * OPTION, which is right for a laptop and wrong to rely on in production. This command asks the
 * database itself, as the connection the application actually uses: it issues an UPDATE and a DELETE
 * on `audit_logs` that match no row (`WHERE 1 = 0`) — privileges are checked before the WHERE runs,
 * so a user who lacks them is refused, and a user who holds them changes nothing.
 *
 * Exit 0 when both are refused; exit 1 when either is allowed, which the deploy treats as a failed
 * step (docs/runbook-deploy.md §4). On a driver without grants (SQLite) it says so and exits 0, or 1
 * with --strict, which the staging and production deploy pass.
 */
final class AuditGrants extends Command
{
    /** MySQL/MariaDB: "command denied to user". */
    private const DENIED = 1142;

    protected $signature = 'track:audit-grants {--strict : Fail when the database has no grants to check}';

    protected $description = 'Verify the application database user cannot UPDATE or DELETE audit_logs';

    public function handle(): int
    {
        $connection = DB::connection();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->warn("Not applicable on {$connection->getDriverName()}: it has no grants. Run this on staging and production.");

            return $this->option('strict') ? self::FAILURE : self::SUCCESS;
        }

        $allowed = self::mutationsAllowed(static fn (string $sql): mixed => $connection->statement($sql));

        if ($allowed === []) {
            $this->info('audit_logs is append-only for this user: UPDATE and DELETE are refused.');

            return self::SUCCESS;
        }

        $this->error('audit_logs is NOT append-only for this user: '.implode(' and ', $allowed).' allowed.');
        $this->line('Run as a DBA: REVOKE UPDATE, DELETE ON `<db>`.`audit_logs` FROM \'<app user>\'@\'%\'; then run this again.');

        return self::FAILURE;
    }

    /**
     * The mutating statements the database user behind $statement may run on the audit table.
     *
     * @param  callable(string): mixed  $statement  runs one SQL statement as the application's user
     * @return list<string>
     */
    public static function mutationsAllowed(callable $statement): array
    {
        $allowed = [];

        foreach (['UPDATE' => 'update audit_logs set id = id where 1 = 0', 'DELETE' => 'delete from audit_logs where 1 = 0'] as $verb => $sql) {
            try {
                $statement($sql);
                $allowed[] = $verb;
            } catch (QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== self::DENIED) {
                    throw $e;
                }
            }
        }

        return $allowed;
    }
}
