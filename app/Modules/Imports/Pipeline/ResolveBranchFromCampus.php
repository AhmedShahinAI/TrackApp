<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

use App\Models\Branch;

/**
 * T330 — a registrar campus value, such as «<campus> (طلاب)», to exactly one branch (FR-099).
 *
 * The registration unit writes campus and gender in one string: the campus name, then the gender in
 * brackets. It is split, the gender read the way RowValidator::gender() reads the central mode's
 * gender column, and the pair matched against the organization's ACTIVE branches by either name.
 *
 * Three answers, kept apart because each is refused with a different reason (spec Edge Cases):
 *   - RESOLVED  — one branch;
 *   - NONE      — no branch has that name and gender;
 *   - AMBIGUOUS — several do. With no gender in brackets, the male and female branches of one campus
 *                 are two matches, and guessing between them would put a section's students on the
 *                 wrong campus.
 *
 * Inactive branches never resolve: importing into a closed campus is how it quietly acquires a new
 * term of sections (RowValidator, same rule).
 */
final class ResolveBranchFromCampus
{
    public const RESOLVED = 'resolved';

    public const NONE = 'none';

    public const AMBIGUOUS = 'ambiguous';

    /**
     * @return array{status: string, branch_id: int|null, campus: string, gender: string|null}
     */
    public function resolve(string $value, int $organizationId): array
    {
        [$campus, $gender] = self::split($value);
        $name = ColumnMapper::normalise($campus);

        $ids = $name === '' ? [] : Branch::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->when($gender !== null, fn ($q) => $q->where('gender', $gender))
            ->get(['id', 'campus_name_ar', 'campus_name_en'])
            ->filter(static fn (Branch $branch): bool => in_array($name, [
                ColumnMapper::normalise((string) $branch->campus_name_ar),
                ColumnMapper::normalise((string) $branch->campus_name_en),
            ], true))
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return [
            'status' => match (count($ids)) {
                0 => self::NONE,
                1 => self::RESOLVED,
                default => self::AMBIGUOUS,
            },
            'branch_id' => count($ids) === 1 ? $ids[0] : null,
            'campus' => $campus,
            'gender' => $gender,
        ];
    }

    /**
     * «<campus> (طلاب)» → ['<campus>', 'male']. Without a bracketed gender, the gender is null.
     * Both bracket styles, since a spreadsheet typed on an Arabic keyboard may use either.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function split(string $value): array
    {
        if (preg_match('/^(.*?)[\(（]([^\)）]*)[\)）]\s*$/u', trim($value), $parts) === 1) {
            $gender = RowValidator::gender($parts[2]);

            // Brackets that hold something other than a gender are part of the campus name.
            if ($gender !== null) {
                return [trim($parts[1]), $gender];
            }
        }

        return [trim($value), null];
    }
}
