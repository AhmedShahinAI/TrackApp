<?php

declare(strict_types=1);

namespace App\Modules\Shared\Logging;

use Illuminate\Database\QueryException;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * SEC-080 (security plan G-10) — no log line carries a secret or a query's personal data.
 *
 * Two leaks, each closed here before the JSON formatter sees the record:
 *
 *   - **Keys.** Anything in the context or extra under a name like password, token, secret, a `_hash`,
 *     a cookie or an authorization header is replaced. The pattern is Auditor's, widened to the
 *     headers a request dump would carry, so the audit log and the application log agree.
 *   - **SQL bindings.** A QueryException's message is the statement with every bound value written
 *     into it — an email, a University ID, a password hash on the way to the users table — and so is
 *     the driver's own message ("Duplicate entry 'x@mu.edu.sa' for key…"). Such an exception is
 *     replaced by its normalised form, rebuilt from the RAW statement (placeholders, no values) with
 *     every quoted value in the driver's message masked. Class, code, file and trace stay, because
 *     they are what makes the line useful.
 *
 * Pushed onto the `json` and `alerts` channels by JsonLogFormatter. Sentry is configured separately
 * (`send_default_pii` and SQL bindings off, config/sentry.php).
 */
final class RedactingProcessor implements ProcessorInterface
{
    private const SENSITIVE = '/password|passwd|token|secret|_hash$|^hash$|cookie|authorization|api[_-]?key/i';

    private const MASK = '[redacted]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->scrub($record->context),
            extra: $this->scrub($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE, $key) === 1) {
                $data[$key] = self::MASK;

                continue;
            }

            if ($value instanceof QueryException) {
                $data[$key] = self::normaliseQueryException($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public static function normaliseQueryException(QueryException $e): array
    {
        $driverMessage = $e->getPrevious()?->getMessage() ?? 'Database error';

        return [
            'class' => QueryException::class,
            'message' => self::maskQuoted($driverMessage).' (Connection: '.$e->getConnectionName().', SQL: '.$e->getSql().')',
            'code' => $e->getCode(),
            'file' => $e->getFile().':'.$e->getLine(),
            'trace' => array_map(
                static fn (array $frame): string => ($frame['file'] ?? '[internal]').':'.($frame['line'] ?? 0),
                array_slice($e->getTrace(), 0, 40),
            ),
            'previous' => $e->getPrevious() instanceof Throwable ? $e->getPrevious()::class : null,
        ];
    }

    /** 'x@mu.edu.sa' and "x" become '?' — a driver quotes exactly the values it was given. */
    private static function maskQuoted(string $message): string
    {
        return (string) preg_replace(["/'[^']*'/", '/"[^"]*"/'], ["'?'", '"?"'], $message);
    }
}
