<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * T048 - the single read path for configuration (constitution IV).
 *
 * Constitution IV says no literal for a configurable value appears in application code. That is only
 * true if reading one is as easy as writing a literal would have been, so this reads the whole table
 * once per request and serves every lookup from that array. The alternative - a query per setting -
 * would make a developer reach for the literal instead, and the principle would quietly stop holding.
 *
 * An unknown key throws rather than returning null. A typo in a setting name is a bug, and a silent
 * null would surface it as a validation rule that permits everything or a limit of zero.
 */
final class Settings
{
    public const CACHE_KEY = 'settings.all';

    public const UNIVERSITY_ID_PATTERN = 'university_id_pattern';

    public const IMPORT_MAX_FILE_MB = 'import_max_file_mb';

    public const IMPORT_MAX_ROWS = 'import_max_rows';

    public const ATTACHMENT_MAX_MB = 'attachment_max_mb';

    public const DEFAULT_LOCALE = 'default_locale';

    public const SESSION_TIMEOUT_MINUTES = 'session_timeout_minutes';

    public const SECTION_DEGREES = 'section_degrees';

    public const SECTION_ACTIVITIES = 'section_activities';

    // T367 — the student support channel (Amendment 2, data-model.md Group 7 "Settings").

    public const SUPPORT_CATEGORIES = 'support_categories';

    public const SUPPORT_DESCRIPTION_MAX = 'support_description_max';

    public const SUPPORT_THROTTLE_IP = 'support_throttle_ip';

    public const SUPPORT_THROTTLE_IDENTIFIER = 'support_throttle_identifier';

    public const SUPPORT_STATUS_THROTTLE_IP = 'support_status_throttle_ip';

    public const SUPPORT_MIN_RESPONSE_MS = 'support_min_response_ms';

    public const SUPPORT_RETENTION_MONTHS = 'support_retention_months';

    public const SUPPORT_CHALLENGE_DIFFICULTY = 'support_challenge_difficulty';

    public static function get(string $key): mixed
    {
        $all = self::all();

        if (! array_key_exists($key, $all)) {
            throw new RuntimeException(
                "Unknown setting [{$key}]. Settings are seeded, not created on demand - add it to SettingSeeder.",
            );
        }

        return $all[$key];
    }

    public static function string(string $key): string
    {
        return (string) self::get($key);
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    /**
     * A settings list, as stored: a list of options each carrying a machine value and its two labels.
     *
     * @return list<array{value: string, ar: string, en: string}>
     */
    public static function options(string $key): array
    {
        /** @var list<array{value: string, ar: string, en: string}> $options */
        $options = self::get($key);

        return $options;
    }

    /**
     * The permitted machine values of a settings list - what a validation rule compares against.
     *
     * @return list<string>
     */
    public static function values(string $key): array
    {
        return array_values(array_map(
            static fn (array $option): string => $option['value'],
            self::options($key),
        ));
    }

    /**
     * A rate limit as stored: how many attempts, over how many minutes. Both halves are settings
     * (research.md R-09), so a limit is one row rather than two that could be edited out of step.
     *
     * A malformed row throws, like an unknown key: a limit that silently read as zero would refuse
     * everyone, and one that read as unlimited would remove the control.
     *
     * @return array{max_attempts: int, decay_minutes: int}
     */
    public static function limit(string $key): array
    {
        $limit = self::get($key);

        if (! is_array($limit)
            || ! is_int($limit['max_attempts'] ?? null) || $limit['max_attempts'] < 1
            || ! is_int($limit['decay_minutes'] ?? null) || $limit['decay_minutes'] < 1) {
            throw new RuntimeException("Setting [{$key}] is not a rate limit of max_attempts and decay_minutes.");
        }

        return ['max_attempts' => $limit['max_attempts'], 'decay_minutes' => $limit['decay_minutes']];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        /** @var array<string, mixed> $settings */
        $settings = Cache::rememberForever(
            self::CACHE_KEY,
            static fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        return $settings;
    }

    /**
     * Drop the cache. Called by the settings write path and by any seeder that changes a row, because
     * a stale cache here means the application disagrees with its own configuration.
     */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
