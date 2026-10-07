<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Actions;

use App\Models\Section;
use App\Models\SectionAccessGrant;
use App\Models\User;
use App\Modules\Audit\Services\Auditor;
use App\Modules\Shared\Actions\TransactionalAction;
use Illuminate\Support\Facades\Auth;

/**
 * T149 — give one instructor access to one additional section (FR-026).
 *
 * The grant and its audit entry share a transaction, like every other write. The entry names the
 * *section* as the entity and carries the recipient in the payload, rather than the reverse: the
 * question an administrator asks later is "who could see this section", and that reading is only
 * cheap if the section is the indexed entity_id.
 *
 * `granted_by` is recorded separately from the audit entry's `user_id`. They are the same person
 * today, but the audit log answers "who performed an action" and the grant row answers "under whose
 * authority does this access exist" — and only the second one survives the audit retention window.
 *
 * @extends TransactionalAction<SectionAccessGrant>
 */
final class GrantSectionAccess extends TransactionalAction
{
    protected function handle(mixed ...$arguments): SectionAccessGrant
    {
        /** @var Section $section */
        $section = $arguments[0];

        /** @var User $instructor */
        $instructor = $arguments[1];

        $grant = SectionAccessGrant::query()->create([
            'section_id' => $section->getKey(),
            'user_id' => $instructor->getKey(),
            'granted_by' => Auth::id(),
            'granted_at' => now(),
        ]);

        app(Auditor::class)->record('section.access_granted', $section, [], [
            'user_id' => $instructor->getKey(),
            'granted_by' => Auth::id(),
        ]);

        /*
         * The recipient's memo is per-instance and this is not their request, so there is nothing to
         * invalidate here — their next request builds a fresh User and reads the new row. The test
         * that would catch a regression is in ScopeChangeTakesEffectImmediatelyTest, which asserts
         * the change lands without re-authentication.
         */
        return $grant;
    }
}
