<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionInstructorHistory;
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

            // Same organization as the branch. Each factory otherwise picks "the first college", so a
            // test that builds a branch in a second organization got a course and programme from the
            // first — a cross-tenant section, produced as the normal case.
            'course_id' => fn (array $attributes) => Course::factory()
                ->state(['organization_id' => self::organizationOf($attributes['branch_id'])]),
            'program_id' => fn (array $attributes) => Program::factory()
                ->state(['organization_id' => self::organizationOf($attributes['branch_id'])]),
            'section_number' => (string) fake()->unique()->numberBetween(100, 9999),
            'instructor_id' => User::factory(),
            'degree' => 'bachelor',
            'activity' => 'lecture',
            'status' => 'active',
            'created_by' => User::factory(),
            'requires_approval' => false,
        ];
    }

    private static function organizationOf(mixed $branchId): int
    {
        return (int) Branch::query()->whereKey($branchId)->value('organization_id');
    }

    /**
     * A factory-built section gets the open history row a real one gets.
     *
     * The endpoint opens this row when it creates a section (SectionController::store), because
     * FR-048 asks who taught it in March and the founding instructor has to be in the answer.
     * Without the same thing here, every factory-built section is in a state the application cannot
     * produce — and a test written against it certifies behaviour that only exists in the test.
     *
     * That is not hypothetical: ChangeInstructorTest seeded this row by hand and passed green for
     * the whole phase while the application created none at all.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Section $section): void {
            if ($section->instructor_id === null) {
                return;
            }

            SectionInstructorHistory::query()->create([
                'section_id' => $section->getKey(),
                'instructor_id' => $section->instructor_id,
                'assigned_at' => $section->created_at ?? now(),
                'unassigned_at' => null,
                'changed_by' => $section->created_by ?? $section->instructor_id,
            ]);
        });
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
