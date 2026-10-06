<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050 + T305.
 *
 * `status` defaults to `active` and `requires_approval` to false, which is the Super-Admin-created
 * shape (FR-171). A test about the approval workflow states the other shapes explicitly, so a
 * section that silently needed approval cannot leak into a test that is about something else.
 *
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'term_id' => AcademicTerm::factory(),
            'branch_id' => Branch::factory(),
            'course_id' => Course::factory(),
            'program_id' => Program::factory(),
            'section_number' => (string) fake()->unique()->numberBetween(100, 9999),
            'instructor_id' => User::factory(),
            'degree' => 'bachelor',
            'activity' => 'lecture',
            'status' => 'active',
            'created_by' => User::factory(),
            'requires_approval' => false,
        ];
    }

    /**
     * A section with no instructor assigned - the state the sections list renders as "غير مُعيَّن".
     */
    public function withoutInstructor(): static
    {
        return $this->state(fn (array $attributes): array => ['instructor_id' => null]);
    }

    /**
     * Created by someone whose sections need approval (FR-172).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'requires_approval' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'rejected',
            'requires_approval' => true,
            'review_reason' => 'شعبة مكرّرة',
        ]);
    }
}
