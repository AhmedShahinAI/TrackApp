<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * T472 + T487 — one row per approval transition (data-model.md Group 8 `section_approval_history`).
 *
 * Append-only: the record of who decided what. An edit would make it worthless, so an update or a
 * delete throws, and the table has no `updated_at`. Separate from section_instructor_history, which
 * records teaching rather than approval.
 *
 * @property int $id
 * @property int $section_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int $actor_id
 * @property string|null $reason
 * @property Carbon $created_at
 */
final class SectionApprovalHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'section_approval_history';

    /** @var list<string> */
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(fn (): never => throw new LogicException('Approval history is append-only.'));
        self::deleting(fn (): never => throw new LogicException('Approval history is append-only.'));
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
