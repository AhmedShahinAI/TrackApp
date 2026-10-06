<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050.
 *
 * @extends Factory<SectionEnrollment>
 */
class SectionEnrollmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'section_id' => Section::factory(),
            'status' => 'active',
            'enrolled_at' => now(),
            'academic_status' => 'regular',
            'roster_sequence' => null,
        ];
    }

    /**
     * Withdrawn by TRACK, which FR-047 makes impossible without a written reason - so the state sets
     * one rather than leaving a factory able to produce a row the application would refuse.
     */
    public function withdrawn(string $reason = 'انسحاب من المقرر عبر عمادة القبول والتسجيل'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
            'withdraw_reason' => $reason,
        ]);
    }
}
