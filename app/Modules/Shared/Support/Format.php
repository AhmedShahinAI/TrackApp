<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use App\Models\AcademicTerm;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * T270 — the one way a number, a date or a term reaches the screen (FR-084, design-system.md §12).
 *
 * ## The same digits and the same date order in both languages
 *
 * FR-084 asks for Western Arabic digits 0–9 and DD/MM/YYYY whatever the interface language. The
 * views had three habits that broke it in different ways: `isoFormat('LL')`, which in Arabic writes
 * the month as a word ("15 سبتمبر 2026") and in English reorders it ("September 15, 2026"); ad-hoc
 * `format('d/m/Y H:i')`, which was right but repeated in a dozen places; and bare `number_format`,
 * which was right and gave no single place to change. Every one now goes through here.
 *
 * Nothing here consults the locale's digit set. A formatter that did would print Arabic-Indic digits
 * for Arabic the day someone configures Carbon's `ar_SA` numerals, and a university id or a section
 * number in Arabic-Indic digits is a different string to every system it is pasted into.
 *
 * ## Stored in UTC, read in Riyadh
 *
 * The database holds UTC (config/app.php `timezone`) and a reader lives in `display_timezone`. A
 * timestamp is converted before it is formatted. A calendar date — a term's start, a `date` column,
 * which arrives as midnight with no zone of its own — is not: it names a day, not an instant, and
 * converting it would move the day for any zone west of UTC. `date()` tells the two apart by that
 * midnight; `dateTime()` renders only instants and always converts.
 *
 * ## Terms
 *
 * The design system writes a term as "2026/2027 - الأول": the academic year, then the semester in the
 * reader's language. The stored `name_ar` / `name_en` is the term's own label and stays where a term
 * is the thing being listed; anywhere a term is the *context* of something else — a section, an
 * import, a filter — this is how it reads.
 */
final class Format
{
    /** DD/MM/YYYY, on the reader's calendar. */
    public static function date(DateTimeInterface|string|null $value, string $empty = '—'): string
    {
        $date = self::local($value);

        return $date === null ? $empty : $date->format('d/m/Y');
    }

    /**
     * DD/MM/YYYY HH:MM, 24-hour, on the reader's clock — isolated left-to-right.
     *
     * The date and the time are two runs of Western digits with a space between them, and in an
     * Arabic paragraph that space takes the paragraph's direction: "09/10/2026 05:25" was painted as
     * "05:25 09/10/2026", time first, in every Arabic table. U+2066 … U+2069 (LRI … PDI) isolate the
     * value so it reads the same in both directions, wherever it is printed, without each view
     * having to remember a <bdi>.
     */
    public static function dateTime(DateTimeInterface|string|null $value, string $empty = '—'): string
    {
        // Always an instant: a sign-in at 00:00:00 UTC is 03:00 in Riyadh, not a calendar date.
        $date = self::local($value, instant: true);

        return $date === null ? $empty : "\u{2066}".$date->format('d/m/Y H:i')."\u{2069}";
    }

    /** Thousands separated by a comma, Western digits. */
    public static function number(int|float|string|null $value): string
    {
        return number_format((float) ($value ?? 0), is_float($value) && floor($value) !== $value ? 1 : 0);
    }

    /** "2026/2027 - الأول" — the academic year, then the semester in the current language. */
    public static function term(?AcademicTerm $term, ?string $locale = null): string
    {
        if ($term === null) {
            return '—';
        }

        $semester = __('terms.semesters.'.$term->semester, [], $locale);

        return trim($term->academic_year.' - '.$semester, ' -');
    }

    /**
     * The value in the display timezone. `$instant` says the value is a moment, always converted.
     * Without it the value may be a calendar date (a term's start, an enrollment day), stored at
     * midnight, which names a day and must not be shifted — so midnight exactly is left alone.
     * That guess was also applied to instants, and a sign-in at 00:00:00 UTC showed 3 hours early.
     */
    private static function local(DateTimeInterface|string|null $value, bool $instant = false): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);

        // A calendar date names a day; only an instant has a clock to convert.
        if (! $instant && $date->format('H:i:s.u') === '00:00:00.000000') {
            return $date;
        }

        return $date->setTimezone((string) config('app.display_timezone', 'Asia/Riyadh'));
    }
}
