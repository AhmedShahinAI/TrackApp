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
