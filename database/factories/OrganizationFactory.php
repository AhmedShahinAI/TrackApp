<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => 'college',
            'name_ar' => 'كلية '.fake()->unique()->word(),
            'name_en' => 'College of '.fake()->unique()->word(),
            'parent_id' => null,
        ];
    }
}
