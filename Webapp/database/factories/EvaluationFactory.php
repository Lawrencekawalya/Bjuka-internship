<?php

namespace Database\Factories;

use App\Enums\EvaluationType;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intern_id' => Intern::factory(),
            'supervisor_id' => User::factory(),
            'evaluation_type' => EvaluationType::PERIODIC,
            'technical_score' => $this->faker->numberBetween(1, 5),
            'communication_score' => $this->faker->numberBetween(1, 5),
            'teamwork_score' => $this->faker->numberBetween(1, 5),
            'problem_solving_score' => $this->faker->numberBetween(1, 5),
            'conduct_score' => $this->faker->numberBetween(1, 5),
            'attendance_score' => $this->faker->numberBetween(1, 5),
            'strengths' => $this->faker->paragraph(),
            'improvement_areas' => $this->faker->paragraph(),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
