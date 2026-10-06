<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050.
 *
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', '+6 months');
        $end = (clone $start)->modify('+4 months');
        $year = (int) $start->format('Y');

        return [
            'name_ar' => 'الفصل الدراسي '.fake()->unique()->numberBetween(1000, 9999),
            'name_en' => 'Term '.fake()->unique()->numberBetween(1000, 9999),
            'academic_year' => $year.'/'.($year + 1),
            'semester' => fake()->randomElement(['first', 'second', 'summer']),
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'is_current' => false,
        ];
    }

    /**
     * The one current term (FR-036). Deliberately not the default: a factory that made every term
     * current would make the invariant impossible to test.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
            'is_current' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'draft', 'is_current' => false]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'closed', 'is_current' => false]);
    }
}
