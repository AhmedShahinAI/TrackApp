<?php

declare(strict_types=1);

namespace App\Modules\Support\Actions;

use App\Models\SupportRequest;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Settings\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * T451 — the retention sweep (FR-159, research.md R-12, constitution v1.1.0).
 *
 * A request raised more than `support_retention_months` ago has **exactly three columns** cleared —
 * `student_id`, `contact`, `submitted_ip` — and `anonymised_at` stamped. Everything else stays: the
 * reference, the category, the description, the status, the state history (its `support.status_changed`
 * entries) and every response. Nothing is deleted (contracts/support-staff.md: retention anonymises, it
 * never deletes).
 *
 * The clock runs from **creation**, not closure, so a request abandoned without ever being closed is not
 * kept forever.
 *
 * One transaction and one `support.anonymised` entry per request (actor null, `channel: system`). The
 * entry carries no old values: copying the cleared student and IP into it would keep them for the audit
 * log's five years, which is the retention this sweep exists to end. The audit entries already written
 * about the request are not touched (FR-160) — they follow the longer audit retention.
 *
 * ## One row never stops the rest (Phase 25 review, H-2)
 *
 * Rows are taken in id order, so a row that fails would fail first on every run, and every expired
 * request after it would be kept forever. Each row's failure is reported and the sweep moves on.
 * The run still ends in an exception if any row failed, so the job is recorded as failed and the
 * monitor sees it.
 *
 * The write is `saveQuietly()`. The model's `saving` rules guard what staff do (a rejection needs a
 * reason), and a row that predates or bypassed one must still be anonymised: retention is the legal
 * obligation, and the data-quality problem is a separate one. No other model event applies here,
 * because the audit trait is wired for creation only and the sweep writes its own named entry.
 *
 * Every query drops the global scopes. The sweep runs with no one signed in, so the scopes are inert
 * anyway, but a sweep that saw only what a caller sees would be one called from the wrong place.
 */
final class AnonymiseExpiredRequests
{
    private const CHUNK = 500;

    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Anonymise every request past its retention; returns how many were anonymised.
     *
     * @throws RuntimeException when the period is unusable, or after the run when any row failed
     */
    public function execute(?Carbon $now = null): int
    {
        $months = Settings::int(Settings::SUPPORT_RETENTION_MONTHS);

        // A zero or negative period would anonymise every request the moment it was raised, including
        // the ones a coordinator is answering now. Refuse rather than read it as "keep nothing".
        if ($months < 1) {
            throw new RuntimeException('support_retention_months must be at least 1.');
        }

        $cutoff = ($now ?? now())->copy()->subMonthsNoOverflow($months);
        $count = 0;
        $failed = 0;

        SupportRequest::query()->withoutGlobalScopes()
            ->whereNull('anonymised_at')
            ->where('created_at', '<=', $cutoff)
            ->select('id')
            ->chunkById(self::CHUNK, function ($chunk) use (&$count, &$failed): void {
                foreach ($chunk as $row) {
                    try {
                        $count += $this->anonymise((int) $row->id) ? 1 : 0;
                    } catch (Throwable $e) {
                        // The row's transaction rolled back, so it is untouched and tomorrow's run
                        // retries it.
                        report($e);
                        $failed++;
                    }
                }
            });

        if ($failed > 0) {
            throw new RuntimeException("The retention sweep anonymised {$count} requests; {$failed} could not be anonymised and were reported.");
        }

        return $count;
    }

    private function anonymise(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            /** @var SupportRequest|null $request */
            $request = SupportRequest::query()->withoutGlobalScopes()->whereKey($id)->lockForUpdate()->first();

            // Anonymised by an overlapping run between the chunk and the lock.
            if ($request === null || $request->anonymised_at !== null) {
                return false;
            }

            $request->forceFill([
                'student_id' => null,
                'contact' => null,
                'submitted_ip' => null,
                'anonymised_at' => now(),
            ])->saveQuietly();

            $this->auditor->record('support.anonymised', $request, [], [
                'anonymised_at' => $request->anonymised_at?->toIso8601String(),
                'channel' => 'system',
            ]);

            return true;
        });
    }
}
