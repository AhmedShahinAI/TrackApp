<?php

declare(strict_types=1);

namespace App\Modules\Imports\Modes;

/**
 * T344 — the college's course catalogue, in bulk (FR-103, decision C-05).
 *
 * Four columns: code, both names, and the programmes the course belongs to — several, separated by a
 * comma or the Arabic comma, each named by its code or either of its names (the CourseImportDialog
 * reference does the same). The English name is optional: a missing one takes the Arabic, as the
 * reference does, because a course must carry both and inventing an English name would put words
 * in the catalogue nobody wrote.
 *
 * ## What it does to what already exists
 *
 * An existing code is MATCHED, never duplicated, and its stored names stand — as a student's stored
 * name stands over a roster's (FR-069): a bulk file is a poor authority for renaming a course
 * someone set up. Its named programmes are ADDED to its associations; none is removed, because a
 * file that lists one programme is not a statement that the course left the others.
 *
 * ## Programmes the file names that the platform cannot simply use
 *
 *   - unknown       — warning; the operator chooses at mapping to create it or skip the row
 *                     (`create_missing_programs`, spec acceptance scenario 2);
 *   - deactivated   — warning; associated only if the operator confirms it at mapping
 *                     (`associate_inactive_programs`, spec Edge Cases), otherwise left out while
 *                     the row's other programmes still apply.
 *
 * Same constants as the other modes, so the pipeline asks every mode the same questions.
 */
final class CourseMode
{
    public const NAME = 'course';

    /** @var array<string, bool> target => required */
    public const TARGETS = [
        'course_code' => true,
        'name_ar' => true,
        'name_en' => false,
        'programs' => true,
    ];

    /**
     * Ordered so the more specific name is tried first: ColumnMapper's second pass matches by
     * containment, and «اسم المقرر» is contained in both «…بالعربية» and «…بالإنجليزية».
     *
     * @var array<string, list<string>>
     */
    public const ALIASES = [
        'course_code' => ['رمز المقرر', 'كود المقرر', 'course code', 'course_code', 'code'],
        'name_en' => ['اسم المقرر بالإنجليزية', 'الاسم بالإنجليزية', 'name en', 'name_en', 'english name', 'name (english)'],
        'name_ar' => ['اسم المقرر بالعربية', 'الاسم بالعربية', 'اسم المقرر', 'name ar', 'name_ar', 'arabic name', 'name (arabic)'],
        'programs' => ['البرامج', 'البرنامج', 'programs', 'program'],
    ];

    /** A course code appears once per file (contracts/imports.md, `course` row categories). */
    public const IDENTITY = ['course_code'];

    /** Separators a list of programmes may use: comma, Arabic comma, semicolon, slash, newline. */
    public const LIST_SEPARATORS = '/[,،;؛|\n]+/u';

    /** @return list<string> */
    public static function required(): array
    {
        return array_keys(array_filter(self::TARGETS));
    }

    /** @return list<string> */
    public static function targets(): array
    {
        return array_keys(self::TARGETS);
    }

    /**
     * A cell's list of names, trimmed, empties dropped, duplicates removed in order.
     *
     * @return list<string>
     */
    public static function list(string $value): array
    {
        $items = array_map('trim', preg_split(self::LIST_SEPARATORS, $value) ?: []);

        return array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    }
}
