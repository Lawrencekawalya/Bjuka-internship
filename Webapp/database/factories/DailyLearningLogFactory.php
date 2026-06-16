<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\DailyLearningLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyLearningLog>
 */
class DailyLearningLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'studied_content' => $this->faker->paragraph(),
            'tasks_completed' => $this->faker->paragraph(),
            'challenges' => $this->faker->sentence(),
        ];
    }
}
