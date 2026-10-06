<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Audit\Concerns\Auditable;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * T039 - a student. Soft-deleted only (constitution V): a hard delete would take the enrollment
 * history and every initiative record attached to it, which is the historical record the platform
 * exists to keep.
 */
#[Fillable(['university_id', 'name_ar', 'name_en'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /** @return HasMany<SectionEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(SectionEnrollment::class);
    }

    /** @return BelongsToMany<Section, $this> */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_enrollments')
            ->withPivot(['status', 'academic_status', 'roster_sequence', 'enrolled_at'])
            ->withTimestamps();
    }

    /** @return list<string> */
    public function auditedAttributes(): array
    {
        return ['university_id', 'name_ar', 'name_en', 'deleted_at'];
    }
}
