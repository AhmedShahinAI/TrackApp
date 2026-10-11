<?php

declare(strict_types=1);

namespace App\Modules\AcademicTerms\Actions;

use App\Models\AcademicTerm;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T185 — the term state machine (data-model.md).
 *
 * ```
 * draft ──► active ──► closed ──► archived
 *   ▲          │           │
 *   └──────────┘           └──► active   (reopen; Super Admin + mandatory reason)
 * ```
 *
 * ## The map is the rule
 *
 * Transitions live in a constant rather than in a chain of conditionals, because the question a
 * reader has is "what may follow what", and a table answers it in one glance while an `if` cascade
 * answers it only after being traced. Anything absent is refused — the default is no, which is the
 * direction a state machine should fail in.
 *
 * ## 409, not 422
 *
 * A disallowed transition is not a malformed request: the status is a valid value and the term
 * exists. What is wrong is the *current state* of the thing being acted on, and 409 Conflict is the
 * code that says so. A 422 would send the caller looking at their own payload for a mistake that is
 * not there.
 *
 * ## Reopening is the one transition that costs something
 *
 * `closed → active` needs `term.edit_closed` and a written reason. Closing a term is how an
 * institution says "these records are final"; undoing that silently would make the statement
 * worthless, so the reason is stored in the audit payload and the permission is checked by the
 * controller before this runs.
 *
 * @extends TransactionalAction<AcademicTerm>
 */
final class ChangeTermStatus extends TransactionalAction
{
    /**
     * What may follow what. Absent means refused.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'draft' => ['active'],
        'active' => ['draft', 'closed'],
        'closed' => ['archived', 'active'],

        // Terminal. An archived term is the institution's closed book.
        'archived' => [],
    ];

    /**
     * The transitions that cannot be made without saying why.
     *
     * @var list<string>
     */
    public const REASON_REQUIRED_FROM = ['closed'];

    public static function permits(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Only a permitted transition can need a reason. Asked of a refused one (closed → draft), this
     * said yes, and the controller's field rule answered "a reason is required" to a move that no
     * reason would allow — the person wrote one and was then refused anyway.
     */
    public static function needsReason(string $from, string $to): bool
    {
        return self::permits($from, $to) && in_array($from, self::REASON_REQUIRED_FROM, true);
    }

    protected function handle(mixed ...$arguments): AcademicTerm
    {
        /** @var AcademicTerm $term */
        $term = $arguments[0];

        /** @var string $to */
        $to = $arguments[1];

        /** @var string|null $reason */
        $reason = $arguments[2] ?? null;

        $from = (string) $term->status;

        if (! self::permits($from, $to)) {
            throw new ConflictHttpException(
                // Labels, not keys: the message is read on an Arabic page as often as an English one.
                __('terms.errors.transition_not_permitted', ['from' => __('status.'.$from), 'to' => __('status.'.$to)]),
            );
        }

        if (self::needsReason($from, $to) && ($reason === null || trim($reason) === '')) {
            throw new ConflictHttpException(__('terms.errors.reason_required'));
        }

        $term->forceFill(['status' => $to])->saveQuietly();

        app(Auditor::class)->record('term.status_changed', $term,
            ['status' => $from],
            array_filter([
                'status' => $to,
                'reason' => $reason === null ? null : trim($reason),
            ], static fn (mixed $v): bool => $v !== null),
        );

        return $term->refresh();
    }
}
