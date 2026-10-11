<?php

declare(strict_types=1);

namespace App\Modules\AcademicTerms\Actions;

use App\Models\AcademicTerm;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;

/**
 * T184 — exactly one current term (FR-036).
 *
 * ## Why this is a transaction and not two updates
 *
 * `is_current` carries no database constraint: MySQL has no partial unique index, so "exactly one
 * row where is_current = true" cannot be expressed in the schema. The guarantee lives here instead,
 * which means it lives or dies on the clearing and the setting being one atomic step.
 *
 * Two registrars pressing the button within the same second is not hypothetical — it is what
 * happens on the first day of a term. Without the transaction the interleaving `clear(A); clear(B);
 * set(B); set(A)` leaves two current terms, and every screen that reads "the current term" then
 * picks whichever the database returns first. SingleCurrentTermTest drives the concurrent case.
 *
 * The row is locked for update before anything is written, so a second attempt waits rather than
 * reading a value that is about to change.
 *
 * ## The entry names both terms
 *
 * `term.set_current` carries the term that lost the flag and the one that gained it. "Which term
 * was current in March" is the question this answers a year later, and a payload naming only the
 * winner cannot answer it.
 *
 * @extends TransactionalAction<AcademicTerm>
 */
final class SetCurrentTerm extends TransactionalAction
{
    protected function handle(mixed ...$arguments): AcademicTerm
    {
        /** @var AcademicTerm $term */
        $term = $arguments[0];

        /*
         * Locked, not just read. Without the lock two concurrent calls both see "no current term
         * but mine" between their own read and write, and both commit — the exact race FR-036 is
         * written against, and the one a test without `lockForUpdate` cannot reproduce.
         */
        /*
         * Every term row is locked, not just the current one.
         *
         * `where('is_current', true)->lockForUpdate()` locks the rows it matches — and when no term
         * is current yet it matches none, so it locks nothing. Two concurrent calls naming two
         * different terms then take no conflicting lock, both see "nothing current", and both
         * commit: exactly two current terms, which is the one thing FR-036 forbids.
         *
         * Serialising on a row only works if both transactions want the same row, and here they do
         * not. Locking the whole (small, institution-sized) table is the cheapest correct answer;
         * an advisory lock would be the alternative and would add a cache dependency this action
         * does not otherwise have.
         */
        $all = AcademicTerm::query()->lockForUpdate()->get();

        $previous = $all->where('is_current', true);

        foreach ($previous as $outgoing) {
            if ($outgoing->isNot($term)) {
                $outgoing->forceFill(['is_current' => false])->saveQuietly();
            }
        }

        $term->forceFill(['is_current' => true])->saveQuietly();

        app(Auditor::class)->record('term.set_current', $term,
            ['term_id' => $previous->firstWhere(fn (AcademicTerm $t) => $t->isNot($term))?->getKey()],
            ['term_id' => $term->getKey()],
        );

        return $term->refresh();
    }
}
