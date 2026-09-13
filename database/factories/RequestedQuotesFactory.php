<?php

namespace Database\Factories;

use App\Models\RequestedQuotes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RequestedQuotesFactory extends Factory
{
    protected $model = RequestedQuotes::class;

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
        ];
    }
}
