<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Issue> */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => IssueStatus::CLOSED]);
    }
}
