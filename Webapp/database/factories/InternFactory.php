<?php

namespace Database\Factories;

use App\Enums\InternStatus;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intern>
 */
class InternFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'batch_id' => InternshipBatch::factory(),
            'phone' => $this->faker->phoneNumber(),
            'institution' => $this->faker->company(),
            'course' => $this->faker->jobTitle(),
            'registration_number' => $this->faker->unique()->bothify('REG-####'),
            'status' => InternStatus::ACTIVE,
        ];
    }
}
