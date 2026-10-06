<?php

declare(strict_types=1);

namespace App\Modules\Audit\Concerns;

use App\Modules\Audit\Services\Auditor;

/**
 * T042 - wires a model's create, update, and delete events to the Auditor.
 *
 * The allowlist is the important half. A model declares auditedAttributes() and only those
 * attributes reach a payload, so a hash, a token, or a column added later cannot enter the audit log
 * by default. The Auditor redacts by pattern as a second line, but an allowlist fails closed where a
 * denylist fails open, and the audit log is not a place to fail open.
 *
 * The observers fire inside whatever transaction the caller opened, because booted() model events are
 * synchronous. That is what makes constitution II hold for CRUD-shaped writes without each action
 * remembering to call the Auditor itself.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model): void {
            $model->auditor()->record(
                $model->auditActionFor('created'),
                $model,
                [],
                $model->auditPayload($model->getAttributes()),
            );
        });

        static::updated(function (self $model): void {
            $changed = $model->auditPayload($model->getChanges());

            // Nothing in the allowlist changed - a touch, or a column the model does not audit. An
            // entry here would be noise in the one log that must stay readable.
            if ($changed === []) {
                return;
            }

            $model->auditor()->record(
                $model->auditActionFor('updated'),
                $model,
                $model->auditPayload(array_intersect_key($model->getOriginal(), $changed)),
                $changed,
            );
        });

        static::deleted(function (self $model): void {
            $model->auditor()->record(
                $model->auditActionFor('deleted'),
                $model,
                $model->auditPayload($model->getOriginal()),
                [],
            );
        });
    }

    /**
     * The attributes this model allows into an audit payload.
     *
     * @return list<string>
     */
    abstract public function auditedAttributes(): array;

    /**
     * The action key for an event, e.g. `section.created`.
     *
     * Overridable: several keys in contracts/audit.md are not CRUD-shaped - `term.set_current` and
     * `section.instructor_changed` are updates with their own names - and those call the Auditor
     * directly from their action class rather than through this trait.
     */
    public function auditActionFor(string $event): string
    {
        return $this->auditEntityKey().'.'.$event;
    }

    public function auditEntityKey(): string
    {
        return str(class_basename($this))->snake()->toString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditPayload(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip($this->auditedAttributes()));
    }

    protected function auditor(): Auditor
    {
        return app(Auditor::class);
    }
}
