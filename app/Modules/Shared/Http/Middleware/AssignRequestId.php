<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * T275 — one id per request, on every log line it writes and on the response (PRD 8.8).
 *
 * The id goes into Laravel's Context, not only onto the request: Context is attached to every log
 * record and is carried into the jobs a request dispatches, so a validation job's log lines share the
 * id of the upload that queued it. That is the one join a support engineer needs at 2 a.m.
 *
 * An upstream `X-Request-Id` (a load balancer's) is kept when it looks like an id, so one request
 * reads the same in the proxy's log and this one. Anything else is replaced: the header is caller
 * input, and a value with a newline or a JSON fragment in it would forge log lines.
 */
final class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get(self::HEADER, '');
        $id = preg_match('/^[A-Za-z0-9._-]{8,64}$/', $incoming) === 1 ? $incoming : (string) Str::uuid();

        $request->attributes->set('request_id', $id);
        Context::add('request_id', $id);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }
}
