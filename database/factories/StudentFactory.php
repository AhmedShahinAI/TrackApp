<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050.
 *
 * The identifier is nine digits because that is what the seeded `university_id_pattern` requires
 * (FR-043). The factory does not read the setting: a factory that depended on seeded configuration
 * would fail in any test that had not run SettingSeeder, and the point of a factory is to need
 * nothing.
 *
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'university_id' => (string) fake()->unique()->numberBetween(440000000, 449999999),
            'name_ar' => fake()->firstName().' '.fake()->lastName(),
            'name_en' => fake()->firstName().' '.fake()->lastName(),
        ];
    }

    /**
     * A roster row that arrived from an import with no English name - the common case (FR-045).
     */
    public function withoutEnglishName(): static
    {
        return $this->state(fn (array $attributes): array => ['name_en' => null]);
    }
}
