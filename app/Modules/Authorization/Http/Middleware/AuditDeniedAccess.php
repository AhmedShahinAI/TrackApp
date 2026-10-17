<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Http\Middleware;

use App\Modules\Audit\Services\Auditor;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * T148 — records `access.denied` for a refusal, whichever shape it took (FR-024, FR-025).
 *
 * ## Why it catches rather than reads the response
 *
 * The obvious implementation inspects `$next($request)` and records when the status is 403 or 404.
 * It records almost nothing. Both refusals arrive as exceptions — `AuthorizationException` from
 * `can:`, `ModelNotFoundException` from a scoped route binding — and an exception thrown downstream
 * propagates *past* this middleware to the kernel's handler, which is where it becomes a response.
 * By the time a status code exists, this middleware has long since returned.
 *
 * So it catches, records, and rethrows unchanged. The rethrow matters: swallowing the exception
 * here would hand the caller a 200 and make the refusal invisible to them as well as to the log.
 *
 * ## What the caller is told, versus what is written down
 *
 * Nothing changes about the response. A scope miss stays a 404 with no detail (constitution I); the
 * entry records that it was a 404 *and* what was attempted. That asymmetry is the feature: one
 * mistyped id and a thousand sequential ids look identical to the person making them and completely
 * different in the log.
 *
 * Unauthenticated requests are not recorded. A 401 is not a denial of something the caller was
 * reaching for, and auditing them would fill the table with unauthenticated scanner traffic.
 */
final class AuditDeniedAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $status = self::statusFor($exception);

            if ($status !== null) {
                $this->record($request, $status);
            }

            throw $exception;
        }

        // A refusal rendered as a response rather than thrown — an explicit abort(403), say.
        if (in_array($response->getStatusCode(), [403, 404], true)) {
            $this->record($request, $response->getStatusCode());
        }

        return $response;
    }

    /**
     * The status a refusal will become, or null when the exception is not a refusal.
     */
    private static function statusFor(Throwable $exception): ?int
    {
        return match (true) {
            // A policy may refuse as not found (Response::denyAsNotFound) — record the 404 it becomes.
            $exception instanceof AuthorizationException => $exception->hasStatus() ? (int) $exception->status() : 403,
            $exception instanceof AccessDeniedHttpException => 403,

            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => 404,

            default => null,
        };
    }

    private function record(Request $request, int $status): void
    {
        if ($request->user() === null) {
            return;
        }

        app(Auditor::class)->record('access.denied', null, [], [
            'route' => $request->path(),
            'method' => $request->method(),
            'status' => (string) $status,
        ]);
    }
}
