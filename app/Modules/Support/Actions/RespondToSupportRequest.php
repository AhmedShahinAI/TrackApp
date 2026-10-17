<?php

declare(strict_types=1);

namespace App\Modules\Support\Actions;

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use App\Modules\Support\Enums\SupportRequestStatus;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * T414 — a staff response to a support request (FR-150, FR-151).
 *
 * Refused on a `closed` request with 409: a closed request is final and accepts no further response.
 * Checked on the row locked for this transaction, so a response cannot slip in between someone else's
 * close and its commit.
 *
 * Responding to a `new` request first opens it — `new` → `in_progress`, as opening its page would —
 * because answering is the clearest evidence someone is handling it, and a request with a reply still
 * labelled "new" would mislead the next person in the queue.
 *
 * Audited as `support.responded` on the request, with the response's id and **not** its body: the body
 * is in its own append-only row already, and copying it into the audit payload would only duplicate
 * text into a store with a different retention.
 *
 * @extends TransactionalAction<SupportResponse>
 */
final class RespondToSupportRequest extends TransactionalAction
{
    public function __construct(
        private readonly Auditor $auditor,
        private readonly TransitionSupportRequest $transition,
    ) {}

    /**
     * Called as execute($request, $body, $actor).
     */
    protected function handle(mixed ...$arguments): SupportResponse
    {
        /** @var SupportRequest $request */
        /** @var string $body */
        /** @var User $actor */
        [$request, $body, $actor] = $arguments;

        /** @var SupportRequest $locked */
        $locked = SupportRequest::query()->withoutGlobalScopes()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status === SupportRequestStatus::Closed) {
            throw new ConflictHttpException(__('support.staff.errors.closed'));
        }

        // Inside this transaction already, on the row this transaction has locked; the transition's
        // own runs as a nested savepoint and changes nothing if the request is no longer new.
        $locked = $this->transition->openIfNew($locked, $actor);

        $response = new SupportResponse(['body' => $body]);
        $response->forceFill([
            'support_request_id' => $locked->getKey(),
            'user_id' => $actor->getKey(),
        ])->save();

        $this->auditor->record('support.responded', $locked, [], ['response_id' => $response->getKey()]);

        return $response;
    }
}
