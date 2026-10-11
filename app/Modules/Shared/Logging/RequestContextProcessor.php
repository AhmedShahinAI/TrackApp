<?php

declare(strict_types=1);

namespace App\Modules\Shared\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * T275 — who and where, on every log record written during a request (PRD 8.8).
 *
 * Read when the record is written, not when the request starts: the user is known only after the
 * session and the guard have run, and the route only after routing. The request id itself comes from
 * Context (AssignRequestId), which Laravel already adds to every record.
 *
 * Asks each guard whether it *has* a user rather than for the user. `user()` would resolve one — read
 * the session, query the database — from inside a log call, and a log written while that resolution
 * fails would recurse into it.
 */
final class RequestContextProcessor implements ProcessorInterface
{
    private const GUARDS = ['web', 'sanctum'];

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;

        // A routed request only. An artisan command or a queue worker holds a placeholder request
        // with no route, and its records carry the dispatching request's id through Context instead.
        $route = app()->bound('request') ? request()->route() : null;

        if (is_object($route)) {
            $request = request();

            $extra['user_id'] = $this->userId();
            $extra['route'] = $route->getName() ?? $route->uri();
            $extra['method'] = $request->getMethod();
            $extra['path'] = '/'.ltrim($request->path(), '/');
        }

        return $record->with(extra: $extra);
    }

    private function userId(): int|string|null
    {
        if (! app()->bound('auth')) {
            return null;
        }

        foreach (self::GUARDS as $name) {
            try {
                $guard = app('auth')->guard($name);
            } catch (Throwable) {
                continue;
            }

            if ($guard->hasUser()) {
                return $guard->id();
            }
        }

        return null;
    }
}
