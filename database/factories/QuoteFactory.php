<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'message' => $this->faker->sentence(),
            'under_warranty' => $this->faker->boolean(),
            'status' => QuoteStatus::NEW,
        ];
    }

    public function contacted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::CONTACTED,
            ];
        });
    }

    public function accepted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::ACCEPTED,
            ];
        });
    }

    public function rejected(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::REJECTED,
            ];
        });
    }
}
