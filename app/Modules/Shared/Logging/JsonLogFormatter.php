<?php

declare(strict_types=1);

namespace App\Modules\Shared\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Logger as MonologLogger;

/**
 * T275 — the `json` channel's tap: one JSON object per line, with the request's context (PRD 8.8).
 *
 * One line per record so a log shipper can split on newlines without a multi-line parser, and
 * stack traces kept inside the record (`includeStacktraces`) rather than spilled across lines, where
 * they would arrive as dozens of unrelated events.
 */
final class JsonLogFormatter
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        // The `json` channel is a Monolog driver; anything else has no handlers to format.
        if (! $monolog instanceof MonologLogger) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, appendNewline: true);
                $formatter->includeStacktraces();
                $handler->setFormatter($formatter);
            }

            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor(new RequestContextProcessor);
                // SEC-080 (G-10): last pushed runs first, so nothing reaches the formatter unscrubbed.
                $handler->pushProcessor(new RedactingProcessor);
            }
        }
    }
}
