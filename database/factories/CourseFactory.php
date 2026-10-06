<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T050.
 *
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->where('type', 'college')->value('id')
                ?? Organization::factory(),
            'code' => mb_strtoupper(fake()->unique()->bothify('???###')),
            'name_ar' => 'مقرر '.fake()->unique()->word(),
            'name_en' => 'Course '.fake()->unique()->word(),
        ];
    }
}
