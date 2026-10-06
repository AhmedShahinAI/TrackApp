<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T035 - a configuration row (constitution IV).
 *
 * `value` is always JSON, so a setting can be a pattern, a number, or a list of labelled options
 * without the column type deciding in advance which. Application code reads these through the
 * Settings facade, never through this model directly, so the cache stays the single read path.
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model
{
    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
