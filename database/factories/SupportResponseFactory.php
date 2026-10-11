<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T364.
 *
 * @extends Factory<SupportResponse>
 */
class SupportResponseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'support_request_id' => SupportRequest::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
