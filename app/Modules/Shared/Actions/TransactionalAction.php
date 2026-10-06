<?php

declare(strict_types=1);

namespace App\Modules\Shared\Actions;

use Illuminate\Support\Facades\DB;

/**
 * T043 - the base class for any action that writes and audits.
 *
 * Constitution II requires the business write and its audit entry to share one transaction. Leaving
 * that to each action means every new action is a fresh chance to forget, and the symptom of
 * forgetting is invisible: everything works until an audit insert fails, and then the business row
 * survives without its entry. Inheriting from here makes the guarantee structural.
 *
 * @template TResult
 */
abstract class TransactionalAction
{
    /**
     * The action's work. Runs inside a transaction; throw to abort it.
     *
     * @return TResult
     */
    abstract protected function handle(mixed ...$arguments): mixed;

    /**
     * @return TResult
     */
    public function execute(mixed ...$arguments): mixed
    {
        return DB::transaction(fn (): mixed => $this->handle(...$arguments));
    }
}
