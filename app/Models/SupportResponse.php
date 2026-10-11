<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SupportResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * T364 — a staff response to a support request. Append-only.
 *
 * A response is a statement made to a student at a point in time. Editing it after the student has
 * read it would make the status page unreliable as a record, so a correction is a new response
 * (data-model.md `support_responses`). Three layers hold that:
 *
 *   • the table has no `updated_at` column (T362), so `UPDATED_AT` is null here;
 *   • `updating` and `deleting` throw, so no code path — a controller, a tinker session, a future
 *     "edit" button — can change or remove one through the model;
 *   • no route updates or deletes one (an architecture test asserts it in Phase 21).
 *
 * The request's own deletion cascades at the database level, which bypasses these events. Requests
 * are never deleted in practice — the retention sweep anonymises them and keeps the row — so that
 * cascade is the honest foreign key rather than a path anyone uses.
 *
 * Not `Auditable`: the contract's key is `support.responded`, written by the respond action with the
 * request as its entity, not a `support_response.created` from this model.
 */
#[Fillable(['body'])]
class SupportResponse extends Model
{
    /** @use HasFactory<SupportResponseFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('A support response is never edited; add a new response instead.');
        });

        static::deleting(static function (): never {
            throw new LogicException('A support response is never deleted.');
        });
    }

    /** @return BelongsTo<SupportRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'support_request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
