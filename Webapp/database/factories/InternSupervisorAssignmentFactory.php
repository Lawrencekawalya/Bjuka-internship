<?php

namespace Database\Factories;

use App\Models\Intern;
use App\Models\InternSupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternSupervisorAssignment>
 */
class InternSupervisorAssignmentFactory extends Factory
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
            'assigned_at' => now(),
        ];
    }
}
