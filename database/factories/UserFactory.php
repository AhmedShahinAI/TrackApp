<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * T050.
 *
 * The hash is computed once and reused. Argon2id at the configured cost is ~30 ms, which is the
 * point of a password hash, but a test seeding 50 users would pay 1.5 seconds for no benefit - every
 * one of them has the same password anyway.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->where('type', 'college')->value('id')
                ?? Organization::factory(),
            'name_ar' => fake()->firstName().' '.fake()->lastName(),
            'name_en' => fake()->firstName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'locale' => 'ar',
            'is_active' => true,
            'first_login_at' => now()->subDays(10),
            'last_login_at' => now()->subDay(),
        ];
    }

    /**
     * An account created but never used - the state the first-sign-in banner renders for (FR-012).
     */
    public function neverSignedIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'first_login_at' => null,
            'last_login_at' => null,
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
