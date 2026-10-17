<?php

declare(strict_types=1);

namespace App\Modules\Support\Http;

use App\Models\SectionEnrollment;
use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Models\User;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Support\Format;
use App\Modules\Support\Enums\SupportRequestStatus;
use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;

/**
 * T411 + T412 — one shape for a support request, used by the JSON API and the staff screens alike, so
 * a field cannot be present in one and missing from the other.
 *
 * Two things here are deliberate rather than presentational:
 *
 *   • `contact` is `{ value, verified: false }`, never a bare string (FR-145). The student typed it and
 *     nobody checked it; a client handed a plain string would render it as if it were a confirmed
 *     address. The flag is in the payload so no client can lose it.
 *   • A student is shown as **removed** when the request has been anonymised (R-12) — the row stays,
 *     the person does not.
 *
 * Branches are the ones the student holds an **active** enrollment in, read through the scoped
 * relations: a coordinator sees their own branch names, the Super Admin all of them.
 */
final class SupportRequestPresenter
{
    /** The latest responses a detail carries; `responses_total` says how many exist. */
    public const THREAD_LIMIT = 50;

    /** The latest state changes a detail carries; `history_total` says how many exist. */
    public const HISTORY_LIMIT = 50;

    /**
     * The relations a list or a detail needs, eager-loaded in one go so a page of 25 rows is a fixed
     * handful of queries rather than one per row.
     *
     * @return array<int|string, mixed>
     */
    public static function relations(): array
    {
        return [
            'student',
            // The same "currently held" as the scope (Phase 23 review): the branch column named
            // branches that grant no visibility — a registrar-withdrawn or pending-section enrollment.
            'student.enrollments' => fn ($query) => $query->currentlyHeld(),
            'student.enrollments.section.branch',
            'assignee',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function row(SupportRequest $request): array
    {
        $locale = app()->getLocale();
        $created = $request->created_at;
        $waiting = $created !== null
            && in_array($request->status, [SupportRequestStatus::New, SupportRequestStatus::InProgress], true);

        return [
            'reference' => $request->display_reference,
            'student' => self::student($request),
            'branches' => self::branches($request),
            'category' => ['value' => $request->category, 'label' => self::categoryLabel($request->category)],
            'status' => ['value' => $request->status->value, 'label' => __($request->status->labelKey())],
            'assignee' => self::person($request->assignee),
            'created_at' => $created?->toIso8601String(),
            // FR-137: computed, because "how long has this been sitting" is the question the queue
            // answers. Seconds for sorting and for a client to format; a label for people.
            //
            // Only while it is waiting on staff (Phase 21 review): `new` and `in_progress`. A resolved,
            // rejected or closed request is not sitting anywhere, and a rejection three months old
            // reading "waiting 3 months" told the queue the opposite of the truth. Null, not zero —
            // zero would sort it among the requests that just arrived.
            'waiting_seconds' => $waiting ? (int) $created->diffInSeconds(now(), true) : null,
            'waiting_label' => $waiting ? self::duration((int) $created->diffInSeconds(now(), true)) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(SupportRequest $request): array
    {
        /*
         * The latest THREAD_LIMIT responses and HISTORY_LIMIT state changes, with the totals (Phase 21
         * review). Every response and every audit row was hydrated on every view, so one long-running
         * request grew a page's memory and time without bound. The totals let a reader see that
         * earlier entries exist; the full record stays in the audit log and the responses table.
         */
        $responsesTotal = $request->responses()->count();
        $responses = $request->responses()->getQuery()->reorder()
            ->latest('created_at')->latest('id')
            ->limit(self::THREAD_LIMIT)
            ->with('author')
            ->get()
            ->reverse()
            ->values();

        $history = self::history($request);

        return self::row($request) + [
            'description' => $request->description,
            'contact' => ['value' => $request->contact, 'verified' => false],
            'submitted_ip' => $request->submitted_ip,
            'rejection_reason' => $request->rejection_reason,
            'term' => Format::term($request->term),
            'anonymised' => $request->anonymised_at !== null,
            'responses' => $responses->map(fn (SupportResponse $response): array => [
                'body' => $response->body,
                'author' => self::person($response->author),
                'created_at' => $response->created_at?->toIso8601String(),
            ])->all(),
            'responses_total' => $responsesTotal,
            'history' => $history['entries'],
            'history_total' => $history['total'],
        ];
    }

    /**
     * The state history, read from the audit log (T412). The audit log is already the record of who
     * moved the request and when, append-only and immutable; a second history table would be a second
     * record that could disagree with it.
     *
     * Only who, when, from what to what and why are read out of each entry: never the rest of its
     * payload (the originating IP among it) and never the response bodies, which are not in it.
     *
     * @return array{entries: list<array{status: string, label: string, by: array<string, mixed>|null, at: string|null, reason: string|null}>, total: int}
     */
    public static function history(SupportRequest $request): array
    {
        $base = DB::table('audit_logs')
            ->where('audit_logs.entity_type', 'SupportRequest')
            ->where('audit_logs.entity_id', $request->getKey())
            ->whereIn('audit_logs.action', ['support.request_created', 'support.status_changed']);

        $total = (clone $base)->count();

        $entries = (clone $base)
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->orderByDesc('audit_logs.id')
            ->limit(self::HISTORY_LIMIT)
            ->get(['audit_logs.action', 'audit_logs.new_values', 'audit_logs.created_at', 'users.id as user_id', 'users.name_ar', 'users.name_en'])
            ->reverse();

        $history = [];

        foreach ($entries as $entry) {
            /** @var array<string, mixed> $values */
            $values = json_decode((string) $entry->new_values, true) ?: [];
            $status = $entry->action === 'support.request_created' ? 'new' : (string) ($values['status'] ?? '');

            $history[] = [
                'status' => $status,
                'label' => __('support.statuses.'.$status),
                'by' => $entry->user_id === null ? null : [
                    'id' => (int) $entry->user_id,
                    'name' => (string) (app()->getLocale() === 'en' && $entry->name_en ? $entry->name_en : $entry->name_ar),
                ],
                'at' => $entry->created_at === null ? null : Format::dateTime((string) $entry->created_at),
                'reason' => isset($values['rejection_reason'])
                    ? (string) $values['rejection_reason']
                    : (isset($values['reason']) ? (string) $values['reason'] : null),
            ];
        }

        return ['entries' => $history, 'total' => $total];
    }

    /**
     * @return array{name: string|null, university_id: string|null, removed: bool}
     */
    private static function student(SupportRequest $request): array
    {
        $student = $request->student;

        if ($student === null) {
            return ['name' => null, 'university_id' => null, 'removed' => true];
        }

        $name = app()->getLocale() === 'en' && $student->name_en ? $student->name_en : $student->name_ar;

        return ['name' => $name, 'university_id' => $student->university_id, 'removed' => false];
    }

    /**
     * @return list<string>
     */
    private static function branches(SupportRequest $request): array
    {
        $locale = app()->getLocale();
        $names = [];

        /** @var SectionEnrollment $enrollment */
        foreach ($request->student === null ? [] : $request->student->enrollments as $enrollment) {
            $branch = $enrollment->section?->branch;

            if ($branch !== null) {
                $names[$branch->id] = (string) ($branch->{'campus_name_'.$locale} ?? $branch->campus_name_ar);
            }
        }

        return array_values($names);
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private static function person(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $name = app()->getLocale() === 'en' && $user->name_en ? $user->name_en : $user->name_ar;

        return ['id' => (int) $user->getKey(), 'name' => (string) $name];
    }

    /**
     * The category's label in this language, from the setting — falling back to the other language,
     * then to the stored value, so a category since removed from the setting still reads as words.
     */
    public static function categoryLabel(string $value): string
    {
        $locale = app()->getLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        foreach (Settings::options(Settings::SUPPORT_CATEGORIES) as $option) {
            if ($option['value'] === $value) {
                return ($option[$locale] ?? '') !== '' ? $option[$locale] : (($option[$other] ?? '') !== '' ? $option[$other] : $value);
            }
        }

        return $value;
    }

    /**
     * A waiting time in words, in this language: the two largest of days, hours and minutes, Western
     * digits (Phase 24, T449). Carbon's Arabic diffForHumans read «ساعة 30 دقيقة» — two units with no
     * «و» between them, a phrase no Arabic reader writes — and its plural forms are its own; these are
     * the platform's, in lang/{ar,en}/support.php beside every other word on the screen.
     */
    public static function duration(int $seconds): string
    {
        $minutes = intdiv(max(0, $seconds), 60);

        if ($minutes < 1) {
            return __('support.staff.duration.under_a_minute');
        }

        $parts = array_filter([
            'days' => intdiv($minutes, 1440),
            'hours' => intdiv($minutes % 1440, 60),
            'minutes' => $minutes % 60,
        ]);

        $words = [];

        foreach (array_slice($parts, 0, 2, true) as $unit => $count) {
            $words[] = trans_choice('support.staff.duration.'.$unit, $count, ['count' => $count]);
        }

        return implode(__('support.staff.duration.join'), $words);
    }

    /**
     * Canonical reference from whatever was typed, for the queue's `q`.
     */
    public static function referenceFrom(string $typed): ?string
    {
        return ReferenceGenerator::normalise($typed);
    }
}
