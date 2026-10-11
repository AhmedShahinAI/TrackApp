<?php

declare(strict_types=1);

namespace App\Modules\Imports\Modes;

use App\Modules\Imports\Pipeline\ColumnMapper;
use App\Modules\Shared\Rules\UniversityId;

/**
 * T332 — the registration unit's own export (FR-096 to FR-098, decision C-09).
 *
 * ## Four data columns, and a metadata block above them
 *
 * The file the registration unit actually sends describes ONE section: campus, degree, course,
 * activity and section number sit in a label/value block above the header row (MetadataBlockReader),
 * and the rows below carry only sequence, university id, student name and academic status. So the
 * section is resolved once, at upload, from the block, and every row joins it — no row can name
 * another section, as in the section mode.
 *
 * ## Required is university id and name
 *
 * FR-098 says no other column is required. Sequence and academic status are read when present and
 * stored null, with a warning, when missing or unreadable (contracts/imports.md): a roster with one
 * unreadable status is still a roster, and refusing the row would lose the student over a label.
 *
 * Same constants as CentralMode and SectionMode, so the pipeline asks every mode the same questions
 * (ImportJob::modeClass()).
 */
final class RegistrarMode
{
    public const NAME = 'registrar';

    /** @var array<string, bool> target => required, in the order the confirmation panel shows them */
    public const TARGETS = [
        'sequence' => false,
        'university_id' => true,
        'student_name' => true,
        'academic_status' => false,
    ];

    /**
     * The registration unit's own headers first («تسلسل · رقم الطالب · اسم الطالب · الحالة»).
     *
     * No single-letter alias such as «م»: ColumnMapper's second pass matches by containment, and
     * «م» is contained in almost every Arabic header there is.
     *
     * @var array<string, list<string>>
     */
    public const ALIASES = [
        'sequence' => ['تسلسل', 'التسلسل', 'الرقم التسلسلي', 'sequence', 'seq', '#'],
        'university_id' => ['رقم الطالب', 'الرقم الجامعي', 'university id', 'university_id', 'student id', 'student_id'],
        'student_name' => ['اسم الطالب', 'الطالب', 'الاسم', 'student name', 'student_name', 'name'],
        'academic_status' => ['الحالة', 'الحالة الأكاديمية', 'حالة الطالب', 'academic status', 'academic_status', 'status'],
    ];

    /** One section, so the same student twice is the same row twice (FR-057). */
    public const IDENTITY = ['university_id'];

    /**
     * The registrar's words for the three statuses FR-110 permits, after ColumnMapper::normalise()
     * (which folds taa marbuta to haa, so «معتذرة» is listed as «معتذره»).
     *
     * @var array<string, list<string>>
     */
    public const ACADEMIC_STATUSES = [
        'regular' => ['منتظم', 'منتظمه', 'regular'],
        'withdrawn' => ['منسحب', 'منسحبه', 'مسحوب', 'withdrawn'],
        'excused' => ['معتذر', 'معتذره', 'excused'],
    ];

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
     * regular / withdrawn / excused from whatever the file wrote, or null when it is none of them.
     */
    public static function academicStatus(string $value): ?string
    {
        $normalised = ColumnMapper::normalise($value);

        foreach (self::ACADEMIC_STATUSES as $status => $spellings) {
            if (in_array($normalised, $spellings, true)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * The registrar's sequence as a positive integer, or null when it is missing or not a number.
     * Arabic-Indic digits are the same number (UniversityId::normalise).
     */
    public static function sequence(string $value): ?int
    {
        $digits = UniversityId::normalise($value);

        // roster_sequence is an unsigned int; ten digits would overflow it on MySQL.
        if (preg_match('/^\d{1,9}$/', $digits) !== 1 || (int) $digits === 0) {
            return null;
        }

        return (int) $digits;
    }
}
