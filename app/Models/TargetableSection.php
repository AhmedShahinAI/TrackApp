<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Sections\Enums\SectionStatus;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * T495 — FR-174, the most consequential rule of Amendment 4: **only an approved section can be targeted**.
 *
 * A pending, returned or rejected section must never appear in a targeting preview, receive an
 * assignment, or enter a completion denominator. A pending section leaking into one would corrupt
 * every completion figure computed from it.
 *
 * The rule is a **global scope on the targeting query**, as data-model.md Group 8 requires, not a
 * filter at each call site. This class is that query: a Section that cannot be anything but active.
 * Initiative targeting is not built in this release (the initiatives screens are stubs, and the
 * initiative tables exist as schema only, from the EAV spike), so there is no call site yet. This is the
 * one every future one must use. PendingNotTargetableTest fails the build if application code reaches
 * the initiative targeting tables any other way.
 *
 * Read-only. A section's approval state is changed by the approval actions, never through the targeting
 * view of it.
 */
final class TargetableSection extends Section
{
    protected $table = 'sections';

    protected static function booted(): void
    {
        parent::booted();

        self::addGlobalScope('targetable', fn (Builder $query) => $query->where('sections.status', SectionStatus::Active->value));

        self::saving(fn (): never => throw new LogicException('A targetable section is read-only; approval changes go through the approval actions.'));
        self::deleting(fn (): never => throw new LogicException('A targetable section is read-only.'));
    }

    /**
     * The model's factory, morph and audit identity are Section's: this is a view of sections, not
     * another entity.
     */
    public function getMorphClass(): string
    {
        return Section::class;
    }

    public function auditEntityKey(): string
    {
        return 'section';
    }
}
