<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Resources;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Modules\Audit\Services\AuditLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * T164 — one audit entry, as the API and the screens read it.
 *
 * Carries `action_label` and `entity_label` localized server-side, while leaving the raw `action`
 * key untouched. Both halves matter: the label is what a person reads, and the key is what the
 * filter sends back, what the tests assert, and what five years of retained rows are indexed by. A
 * resource that localized the key itself would make last year's entries unfilterable the first time
 * a translation was improved.
 *
 * @mixin AuditLog
 */
final class AuditLogResource extends JsonResource
{
    /**
     * The entity types an entry can name, and how to label one.
     *
     * @var array<string, class-string>
     */
    private const LABELLED = [
        'User' => User::class,
        'Branch' => Branch::class,
        'AcademicTerm' => AcademicTerm::class,
        'Course' => Course::class,
        'Section' => Section::class,
        'Student' => Student::class,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'user' => $this->user === null ? null : [
                'id' => $this->user->id,
                'name_ar' => $this->user->name_ar,
                'name_en' => $this->user->name_en,
            ],
            'action' => $this->action,
            'action_label' => AuditLabels::for((string) $this->action),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity_label' => $this->entityLabel(),
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * A readable name for the record this entry is about.
     *
     * Resolved through the model's own query, so it inherits the caller's scope: an entry that
     * survived the scope because the caller authored it, but whose entity they cannot see, gets the
     * entity's type and id and no name. Loading it unscoped would hand back the name of a section
     * in another branch — the leak this whole phase is built to prevent, through the one field
     * nobody thinks of as data.
     */
    private function entityLabel(): ?string
    {
        $type = (string) $this->entity_type;
        $class = self::LABELLED[$type] ?? null;

        if ($class === null || $this->entity_id === null) {
            return null;
        }

        $record = $class::query()->find($this->entity_id);

        if ($record === null) {
            return null;
        }

        return match ($type) {
            'User' => (string) ($record->name_ar ?? $record->email),
            'Branch' => (string) $record->campus_name_ar,
            'AcademicTerm' => (string) $record->code,
            'Course' => (string) $record->code,
            'Section' => trim(($record->course->code ?? '').' — '.$record->section_number),
            'Student' => (string) $record->name_ar,
            default => null,
        };
    }
}
