<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Models\AuditLog;
use App\Modules\Audit\Services\AuditLabels;
use App\Modules\Audit\Services\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The audit log, read through the same scope as everything else.
 *
 * Per contracts/audit.md a coordinator sees "entries whose entity resolves into their branches,
 * plus their own actions", and `branch_id` filtering "for a coordinator is implicit and cannot be
 * widened". Both halves are the global scope's doing: the filter below can only ever narrow what
 * the scope already allows, because it is applied to the same builder.
 *
 * This replaces the unscoped `DB::table('audit_logs')` query the landing page was using, which
 * showed every coordinator the whole college's activity while its own comment claimed otherwise.
 */
final class AuditLogController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse
    {
        $entries = $this->filtered($request)
            ->with('user:id,name_ar,name_en')
            ->orderByDesc('created_at')
            ->paginate(max(1, min($request->integer('per_page', 25), self::MAX_PER_PAGE)))
            ->withQueryString();

        return response()->json([
            'data' => $entries->getCollection()->map($this->present(...))->all(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    /**
     * FR-023, FR-032 — the same rows the list returns, under the same scope.
     *
     * Exporting the audit log is itself audited: someone taking a copy of the accountability record
     * is exactly the event the accountability record should contain.
     */
    public function export(Request $request): StreamedResponse
    {
        app(Auditor::class)->record('audit.exported', null, [], [
            'filters' => $request->only(['user_id', 'action', 'entity_type', 'date_from', 'date_to']),
        ]);

        $query = $this->filtered($request)->with('user:id,name_ar,name_en');

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['created_at', 'user', 'action', 'entity_type', 'entity_id', 'ip_address']);

            $query->orderByDesc('created_at')->chunk(500, function ($chunk) use ($handle): void {
                foreach ($chunk as $entry) {
                    fputcsv($handle, [
                        $entry->created_at?->toIso8601String(),
                        $entry->user?->name_ar,
                        $entry->action,
                        $entry->entity_type,
                        $entry->entity_id,
                        $entry->ip_address,
                    ]);
                }
            });

            fclose($handle);
        }, 'audit-log.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    private function filtered(Request $request)
    {
        return AuditLog::query()
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->toString()))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', $request->string('entity_type')->toString()))
            ->when($request->filled('entity_id'), fn ($q) => $q->where('entity_id', $request->integer('entity_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<=', $request->date('date_to')));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AuditLog $entry): array
    {
        return [
            'id' => $entry->getKey(),
            'user' => $entry->user === null ? null : [
                'id' => $entry->user->id,
                'name_ar' => $entry->user->name_ar,
                'name_en' => $entry->user->name_en,
            ],
            'action' => $entry->action,

            // Localized server-side, with the raw key kept for filtering (contracts/audit.md).
            'action_label' => AuditLabels::for($entry->action),
            'entity_type' => $entry->entity_type,
            'entity_id' => $entry->entity_id,
            'old_values' => $entry->old_values,
            'new_values' => $entry->new_values,
            'ip_address' => $entry->ip_address,
            'created_at' => $entry->created_at?->toIso8601String(),
        ];
    }
}
