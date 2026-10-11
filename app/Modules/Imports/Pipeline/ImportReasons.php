<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

/**
 * The row-level reasons, stored as keys and rendered in the reader's language (FR-062).
 *
 * ## Keys at rest, sentences at read time
 *
 * A staged row is written once by a queued job that has no reader, and read later by whoever opens
 * the preview or downloads the report — in Arabic by one operator and in English by the next. So the
 * row stores `{column, value, reason_key, params}` and the sentence is produced on the way out. A
 * sentence stored at write time would be in the language of nobody in particular.
 *
 * ## The keys are the contract's
 *
 * `import.duplicate_in_file`, `import.invalid_university_id` and the rest are the identifiers
 * contracts/imports.md lists. The lang files hold them under `imports.reasons.*` without the
 * prefix; localise() does the translation between the two.
 */
final class ImportReasons
{
    // contracts/imports.md, Step 3 — the eight central-mode categories.
    public const DUPLICATE_IN_FILE = 'import.duplicate_in_file';

    public const INVALID_UNIVERSITY_ID = 'import.invalid_university_id';

    public const MISSING_REQUIRED = 'import.missing_required';

    public const UNKNOWN_BRANCH = 'import.unknown_branch';

    public const UNKNOWN_COURSE = 'import.unknown_course';

    public const UNKNOWN_INSTRUCTOR = 'import.unknown_instructor';

    public const NAME_MISMATCH = 'import.name_mismatch';

    public const UNRESOLVABLE_SECTION = 'import.unresolvable_section';

    // contracts/imports.md — the registrar mode's three additional categories (Amendment 1).
    /** The course name matches several courses and the operator has not chosen one (FR-100). */
    public const AMBIGUOUS_COURSE = 'import.ambiguous_course';

    /** Not regular, withdrawn or excused: stored null, the row still imports (FR-110). */
    public const UNKNOWN_ACADEMIC_STATUS = 'import.unknown_academic_status';

    /** Missing or not a number: stored null, the row still imports (FR-112). */
    public const INVALID_SEQUENCE = 'import.invalid_sequence';

    /** The same sequence on two rows of one file: kept, the operator told (review L5). */
    public const DUPLICATE_SEQUENCE = 'import.duplicate_sequence';

    // contracts/imports.md — the course and staff modes' categories (Amendment 1, US8).
    /** A programme the row names does not exist: create it or skip the row, the operator's choice. */
    public const UNKNOWN_PROGRAM = 'import.unknown_program';

    /** A programme name two programmes share: refused, never guessed between. */
    public const AMBIGUOUS_PROGRAM = 'import.ambiguous_program';

    /** A programme the row names is deactivated: associated only on confirmation (spec Edge Cases). */
    public const INACTIVE_PROGRAM = 'import.inactive_program';

    /** The email is held by an account, active or deactivated (FR-005). */
    public const EMAIL_TAKEN = 'import.email_taken';

    /** A coordinator row names no branch (FR-004). */
    public const COORDINATOR_NEEDS_BRANCH = 'import.coordinator_needs_branch';

    /** The role is not a role row (FR-020). */
    public const UNKNOWN_ROLE = 'import.unknown_role';

    /** A value longer than its column holds — refused here rather than truncated at commit. */
    public const TOO_LONG = 'import.too_long';

    /** The course code exists: the course is matched, its stored names stand (informational). */
    public const EXISTING_COURSE = 'import.existing_course';

    // Not problems the contract enumerates, but things the operator must be told about a row.
    public const EXISTING_STUDENT = 'import.existing_student';

    public const INVALID_EMAIL = 'import.invalid_email';

    public const SKIPPED_BY_CHOICE = 'import.skipped_by_choice';

    public const COMMIT_CONFLICT = 'import.commit_conflict';

    /** A section list names a student whose record an administrator deleted (US6). */
    public const STUDENT_UNAVAILABLE = 'import.student_unavailable';

    /** Reasons that describe the row rather than fault it — never in an error report. */
    public const INFORMATIONAL = [self::EXISTING_STUDENT, self::EXISTING_COURSE];

    /**
     * @param  array<string, scalar|null>  $params
     * @return array{column: ?string, value: ?string, reason_key: string, params: array<string, scalar|null>}
     */
    public static function make(string $key, ?string $column = null, ?string $value = null, array $params = []): array
    {
        return [
            'column' => $column,
            'value' => $value === null ? null : mb_substr($value, 0, 500),
            'reason_key' => $key,
            'params' => $params,
        ];
    }

    /**
     * One reason as a sentence, in the current locale unless one is given.
     *
     * A `column` param is a target key and is itself translated, so "قيمة إلزامية مفقودة: الفرع"
     * names the column in the reader's language too.
     *
     * @param  array{column?: ?string, value?: ?string, reason_key: string, params?: array<string, mixed>}  $reason
     */
    public static function localise(array $reason, ?string $locale = null): string
    {
        $params = $reason['params'] ?? [];

        if (isset($params['column']) && is_string($params['column'])) {
            $params['column'] = self::column($params['column'], $locale);
        }

        // The university-id format, described in the reader's language (see RowValidator).
        if (isset($params['digits'])) {
            $params['format'] = __('validation.university_id_digits', ['count' => $params['digits']], $locale);
        } elseif (isset($params['pattern'])) {
            $params['format'] = $params['pattern'];
        }

        $key = 'imports.reasons.'.str_replace('import.', '', $reason['reason_key']);

        // A name mismatch whose stored name may not be shown to this reader (RowValidator).
        if ($reason['reason_key'] === self::NAME_MISMATCH && ! isset($params['stored'])) {
            $key = 'imports.reasons.name_mismatch_private';
        }

        return __($key, array_map(static fn (mixed $v): string => (string) $v, $params), $locale);
    }

    /** A target key as the column label the mapping step shows. */
    public static function column(?string $target, ?string $locale = null): string
    {
        if ($target === null || $target === '') {
            return '—';
        }

        return __('imports.targets.'.$target, [], $locale);
    }

    /**
     * @param  list<array{reason_key: string}>|null  $reasons
     */
    public static function hasProblem(?array $reasons): bool
    {
        foreach ($reasons ?? [] as $reason) {
            if (! in_array($reason['reason_key'], self::INFORMATIONAL, true)) {
                return true;
            }
        }

        return false;
    }
}
