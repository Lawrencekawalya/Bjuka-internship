<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\InternshipBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipBatch>
 */
class InternshipBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_code' => 'INT-'.strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => 'Batch '.$this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'capacity' => $this->faker->numberBetween(10, 30),
            'expected_working_days' => $this->faker->numberBetween(40, 60),
            'status' => BatchStatus::ACTIVE,
        ];
    }
}
