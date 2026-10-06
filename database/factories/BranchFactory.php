<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050. No campus name literal: the architecture test in T052 asserts that no real campus name
 * appears under app/, and a factory that reached for one would invite the same habit there.
 *
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $campus = fake()->unique()->city();

        return [
            'organization_id' => Organization::query()->where('type', 'college')->value('id')
                ?? Organization::factory(),
            'campus_name_ar' => $campus,
            'campus_name_en' => $campus,
            'gender' => fake()->randomElement(['male', 'female']),
            'is_active' => true,
        ];
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes): array => ['gender' => 'male']);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes): array => ['gender' => 'female']);
    }
}
