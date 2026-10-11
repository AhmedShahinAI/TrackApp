<?php

declare(strict_types=1);

namespace App\Modules\Imports\Modes;

/**
 * The central roster's column contract (FR-052, contracts/imports.md).
 *
 * ## Required is the reference's required, not FR-052's list
 *
 * FR-052 names ten values the central import *accepts*. The approved ImportScreens reference marks
 * eight of them mandatory and leaves course name and instructor name optional — correctly, because
 * neither is needed to *find* anything: a course is found by its code and an instructor by their
 * email. They are used only when the operator chooses to create a missing course or account, and
 * then the code or the email stands in when the name column is absent.
 *
 * ## `term` is required and informational
 *
 * The operator chooses the term at upload, and that choice is authoritative: it is what FR-067's
 * "same file for the same term" compares against, and a section is resolved inside it. The column
 * stays mandatory because FR-052 lists it and the registration unit's export carries it, but its
 * value is not matched against term names — "1448-1" and "الفصل الأول 1448" are the same term
 * written two ways, and refusing a whole roster over the spelling would be refusing the file the
 * office actually has.
 *
 * ## `program_code` is an addition, and optional
 *
 * Every section belongs to exactly one programme (FR-090) and FR-052 predates programmes. A new
 * section takes its course's programme when the course has exactly one. When it has none or several
 * — or the course is itself being created — a mapped programme code settles it; without one the row
 * is rejected as unresolvable (FR-068) rather than attached to a programme chosen by guess.
 */
final class CentralMode
{
    public const NAME = 'central';

    /**
     * Target => required. Order is the order the mapping step shows them in.
     *
     * @var array<string, bool>
     */
    public const TARGETS = [
        'term' => true,
        'branch' => true,
        'gender' => true,
        'course_code' => true,
        'course_name' => false,
        'section_number' => true,
        'instructor_name' => false,
        'instructor_email' => true,
        'university_id' => true,
        'student_name' => true,
        'program_code' => false,
    ];

    /**
     * Header spellings each target is suggested from, compared after ColumnMapper::normalise().
     *
     * The Arabic forms are the registration unit's own and the reference's CENTRAL_HEADERS; the
     * English ones are what a file exported from the English interface carries.
     *
     * @var array<string, list<string>>
     */
    public const ALIASES = [
        'term' => ['الفصل', 'الفصل الدراسي', 'الترم', 'term', 'semester'],
        'branch' => ['الفرع', 'المقر', 'الكلية', 'branch', 'campus'],
        'gender' => ['النوع', 'الجنس', 'gender', 'type'],
        'course_code' => ['رمز المقرر', 'رمز', 'كود المقرر', 'course code', 'course_code', 'code'],
        'course_name' => ['اسم المقرر', 'المقرر', 'course name', 'course_name', 'course'],
        'section_number' => ['رقم الشعبة', 'الشعبة', 'section number', 'section_number', 'section', 'sec'],
        'instructor_name' => ['اسم المدرس', 'المدرس', 'اسم عضو هيئة التدريس', 'instructor name', 'instructor_name', 'instructor'],
        'instructor_email' => ['البريد', 'بريد المدرس', 'البريد الإلكتروني', 'instructor email', 'instructor_email', 'email'],
        'university_id' => ['الرقم الجامعي', 'رقم الطالب', 'university id', 'university_id', 'student id', 'student_id', 'student no'],
        'student_name' => ['اسم الطالب', 'الطالب', 'student name', 'student_name', 'name'],
        'program_code' => ['رمز البرنامج', 'البرنامج', 'program code', 'program_code', 'program'],
    ];

    /**
     * The targets that make two rows "the same row" for the in-file duplicate check.
     *
     * The same student in the same section — not the same student anywhere in the file, which is
     * the normal case of one person taking four courses.
     *
     * @var list<string>
     */
    public const IDENTITY = ['branch', 'gender', 'course_code', 'section_number', 'university_id'];

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
}
