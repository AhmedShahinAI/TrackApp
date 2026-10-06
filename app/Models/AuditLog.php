<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * T040 - a read model over the audit log.
 *
 * This class is the second of the three immutability layers (constitution II, research.md R-02). The
 * first is the schema: no updated_at column, so there is nowhere to record a change. The third is
 * the engine: the application database user holds no UPDATE or DELETE on the table.
 *
 * This layer closes the application path. Every mutating entry point Eloquent offers is overridden to
 * throw, and $guarded is the whole attribute set so nothing can be mass-assigned. Overriding rather
 * than merely omitting matters: a developer calling $log->delete() next month gets an immediate,
 * explanatory failure instead of a query that only fails in production, where the grant exists.
 *
 * The only writer is the Auditor service, which inserts through the query builder.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Nothing is mass-assignable. The Auditor does not use this model to write.
     *
     * @var list<string>
     */
    protected $guarded = ['*'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        throw new LogicException(self::refusal('save'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException(self::refusal('update'));
    }

    public function delete(): bool
    {
        throw new LogicException(self::refusal('delete'));
    }

    public function forceDelete(): bool
    {
        throw new LogicException(self::refusal('forceDelete'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    private static function refusal(string $method): string
    {
        return sprintf(
            'The audit log is append-only: %s() is not available on %s. '
            .'If a recorded entry is wrong, record a correcting entry - do not rewrite history.',
            $method,
            self::class,
        );
    }
}
