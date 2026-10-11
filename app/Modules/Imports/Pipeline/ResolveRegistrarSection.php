<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Models\Course;
use App\Models\Section;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\UniversityId;
use Illuminate\Validation\ValidationException;

/**
 * T331 — what a registrar file's metadata block means, resolved at upload (FR-099, FR-100).
 *
 * contracts/imports.md: "The upload step parses the metadata block and attempts resolution
 * immediately, so the operator sees what the file was understood to mean before mapping." So every
 * question the block can answer is answered here, read-only, and the refusals the contract makes a
 * 422 are raised here, before any row is read:
 *
 *   - a campus that resolves to no branch, or to several (FR-099);
 *   - a course name that matches no course (FR-100).
 *
 * A course name that matches SEVERAL courses is not a refusal. The candidates are kept, and the
 * operator chooses one at the confirmation step or lets the rows be skipped — the platform never
 * picks one of two courses with the same name (spec Edge Cases).
 *
 * Nothing here writes. The section, when it does not exist yet, is created at commit like any other
 * import's (FR-102); `section_id` here is only the one that already exists.
 */
final class ResolveRegistrarSection
{
    public function __construct(private readonly ResolveBranchFromCampus $branches) {}

    /**
     * @param  array<string, string>  $metadata
     * @return array{branch_id: int, course_id: int|null, course_candidates: list<int>, section_id: int|null, section_number: string, degree: string|null, activity: string|null}
     */
    public function resolve(array $metadata, int $organizationId, int $termId): array
    {
        $branch = $this->branches->resolve($metadata['campus'], $organizationId);

        if ($branch['status'] !== ResolveBranchFromCampus::RESOLVED) {
            throw ValidationException::withMessages(['file' => __(
                $branch['status'] === ResolveBranchFromCampus::NONE
                    ? 'imports.registrar.campus_unknown'
                    : 'imports.registrar.campus_ambiguous',
                ['value' => $metadata['campus']],
            )]);
        }

        $candidates = $this->courses($metadata['course'], $organizationId);

        if ($candidates === []) {
            throw ValidationException::withMessages(['file' => __('imports.registrar.course_unknown', ['value' => $metadata['course']])]);
        }

        $courseId = count($candidates) === 1 ? $candidates[0] : null;
        $number = RowValidator::sectionNumber($metadata['section']);

        return [
            'branch_id' => (int) $branch['branch_id'],
            'course_id' => $courseId,
            'course_candidates' => count($candidates) > 1 ? $candidates : [],
            'section_id' => $courseId === null ? null : self::existingSection($termId, (int) $branch['branch_id'], $courseId, $number),
            'section_number' => $number,
            'degree' => self::settingValue(Settings::SECTION_DEGREES, $metadata['degree'] ?? ''),
            'activity' => self::settingValue(Settings::SECTION_ACTIVITIES, $metadata['activity'] ?? ''),
        ];
    }

    /**
     * The section a term, branch, course and number already name, or null. Numbers are compared as
     * RowValidator compares them, so «02298» is section 2298; a rejected section has released its
     * number (FR-176) and is not the one a roster means.
     */
    public static function existingSection(int $termId, int $branchId, int $courseId, string $number): ?int
    {
        $found = Section::query()->withoutGlobalScopes()
            ->where('term_id', $termId)
            ->where('branch_id', $branchId)
            ->where('course_id', $courseId)
            ->where('status', '!=', 'rejected')
            ->get(['id', 'section_number'])
            ->first(static fn (Section $section): bool => RowValidator::sectionNumber((string) $section->section_number) === $number);

        return $found === null ? null : (int) $found->id;
    }

    /**
     * FR-100 — the courses a registrar's course value names: by code if it is one, else by Arabic
     * name, compared after ColumnMapper::normalise so «برمجة 2» and «برمجه ٢» are one name.
     * The English name is accepted too, for an export taken from an English interface.
     *
     * @return list<int>
     */
    private function courses(string $value, int $organizationId): array
    {
        $courses = Course::query()->where('organization_id', $organizationId)->get(['id', 'code', 'name_ar', 'name_en']);

        $byCode = $courses->filter(static fn (Course $c): bool => RowValidator::code((string) $c->code) === RowValidator::code($value));

        if ($byCode->isNotEmpty()) {
            return $byCode->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all();
        }

        $name = self::name($value);

        return $courses
            ->filter(static fn (Course $c): bool => $name !== '' && in_array($name, [self::name((string) $c->name_ar), self::name((string) $c->name_en)], true))
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /** A course name for comparison: normalised, with Arabic-Indic digits folded. */
    private static function name(string $value): string
    {
        return ColumnMapper::normalise(UniversityId::normalise($value));
    }

    /**
     * The settings-list value a registrar label names («الدبلوم المتوسط» → intermediate_diploma), by
     * the option's machine value or either of its labels. Null when the list has no such option: the
     * section then takes the list's first value if it is created, and an existing one keeps its own.
     */
    public static function settingValue(string $key, string $label): ?string
    {
        $wanted = ColumnMapper::normalise($label);

        if ($wanted === '') {
            return null;
        }

        foreach (Settings::options($key) as $option) {
            foreach ([$option['value'], $option['ar'], $option['en']] as $candidate) {
                if (ColumnMapper::normalise((string) $candidate) === $wanted) {
                    return $option['value'];
                }
            }
        }

        return null;
    }
}
