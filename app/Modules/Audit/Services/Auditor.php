<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * T041 - the single write path into the audit log (constitution II, research.md R-02).
 *
 * The insert runs on the **current** connection with no deferral of any kind: no afterCommit hook,
 * no queued job, no second connection. That is the whole point. Constitution II requires that a
 * failed audit write abort the action it describes, and anything deferred completes the business
 * write and then loses the entry - which is precisely the failure the principle exists to prevent.
 * Callers wrap the pair in a transaction (see TransactionalAction), so a failure here rolls back the
 * business row with it.
 *
 * The insert is a query-builder insert rather than AuditLog::create(). The model deliberately has no
 * mass-assignable create path, because the only thing that may write to the table is this service.
 */
final class Auditor
{
    /**
     * Keys whose values never enter an audit payload, matched case-insensitively anywhere in the key.
     *
     * The contract (contracts/audit.md) states that no password, hash, session token, or CSRF token
     * is ever recorded. Enforcing it here rather than at each call site means a model added next
     * month inherits the guarantee instead of relying on its author remembering the rule.
     */
    private const REDACTED_KEY_PATTERN = '/password|token|secret|_hash$/i';

    /**
     * Stands in for the IP of a write that has no request behind it - a seeder, a console command, a
     * queued job. The column is not nullable, and a placeholder that is obviously not a real address
     * is more honest than inventing 127.0.0.1, which would read as a genuine local sign-in.
     */
    private const NO_REQUEST_IP = '0.0.0.0';

    public function __construct(private readonly Request $request) {}

    /**
     * Record one audit entry.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(string $action, ?Model $entity = null, array $old = [], array $new = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity === null ? null : class_basename($entity),
            'entity_id' => $entity?->getKey(),
            'old_values' => $this->encode($old),
            'new_values' => $this->encode($new),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function encode(array $values): ?string
    {
        $redacted = $this->redact($values);

        return $redacted === [] ? null : json_encode($redacted, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Strip every sensitive key, at any depth.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function redact(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match(self::REDACTED_KEY_PATTERN, $key) === 1) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $clean;
    }

    private function ip(): string
    {
        if (app()->runningInConsole()) {
            return self::NO_REQUEST_IP;
        }

        return $this->request->ip() ?? self::NO_REQUEST_IP;
    }

    private function userAgent(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $agent = $this->request->userAgent();

        // The column is varchar(500). A longer header is truncated rather than refused, because
        // losing the entry over a verbose user agent would be the wrong trade.
        return $agent === null ? null : mb_substr($agent, 0, 500);
    }
}
