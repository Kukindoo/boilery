<?php

namespace Database\Factories;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    public function customer(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Roles::CUSTOMER->value);
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Roles::ADMIN->value);
        });
    }
}
