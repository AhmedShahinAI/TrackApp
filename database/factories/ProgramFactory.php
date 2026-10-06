<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T305 - needed by SectionFactory, because `sections.program_id` is non-nullable (FR-090).
 *
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->where('type', 'college')->value('id')
                ?? Organization::factory(),
            'code' => mb_strtoupper(fake()->unique()->lexify('???')),
            'name_ar' => 'برنامج '.fake()->unique()->word(),
            'name_en' => 'Program '.fake()->unique()->word(),
            'unit' => fake()->randomElement(['admin', 'tech']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
