<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T037 + T301 - a course. Not soft-deleted: a course with no sections can be edited, and one with
 * sections cannot be removed at all (the foreign key restricts), so there is nothing for a soft
 * delete to express.
 */
#[Fillable(['organization_id', 'code', 'name_ar', 'name_en'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use Auditable, HasFactory;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /** @return BelongsToMany<Program, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    /**
     * The programmes a NEW section of this course may be placed in: the active ones (FR-092, spec
     * Edge Cases). A deactivated programme keeps the sections it already holds but takes no new
     * ones, so an import deciding "the course's one programme" must not count it — counted, a course
     * whose only programme was deactivated kept receiving new sections under it.
     *
     * @return list<int>
     */
    public function activeProgramIds(): array
    {
        return $this->programs()
            ->where('programs.is_active', true)
            ->orderBy('programs.id')
            ->pluck('programs.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['organization_id', 'code', 'name_ar', 'name_en'];
    }
}
