<?php

declare(strict_types=1);

namespace App\Modules\Imports\Modes;

/**
 * The instructor's section list (FR-053, T255): university id and student name, into one section.
 *
 * ## The section is the job's, never the file's
 *
 * The instructor uploads from the page of a section they teach, and the job carries that section.
 * Nothing in the file can name another one — there is no section column to read — so a row cannot
 * attach a student anywhere the instructor was not already allowed to act (FR-054). That is why the
 * contract is two columns and not the central mode's ten.
 *
 * Same constants as CentralMode, so the pipeline asks either mode the same questions
 * (ImportJob::modeClass()).
 */
final class SectionMode
{
    public const NAME = 'section';

    /** @var array<string, bool> target => required */
    public const TARGETS = [
        'university_id' => true,
        'student_name' => true,
    ];

    /** @var array<string, list<string>> */
    public const ALIASES = [
        'university_id' => ['الرقم الجامعي', 'رقم الطالب', 'university id', 'university_id', 'student id', 'student_id', 'student no'],
        'student_name' => ['اسم الطالب', 'الطالب', 'الاسم', 'student name', 'student_name', 'name'],
    ];

    /**
     * Within one section, the same student twice is the same row twice (FR-057).
     *
     * @var list<string>
     */
    public const IDENTITY = ['university_id'];

    /**
     * Both columns: a section list has nothing optional in it (FR-053).
     *
     * @return list<string>
     */
    public static function required(): array
    {
        return array_keys(self::TARGETS);
    }

    /** @return list<string> */
    public static function targets(): array
    {
        return array_keys(self::TARGETS);
    }
}
