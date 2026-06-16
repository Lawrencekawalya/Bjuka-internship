<?php

namespace Database\Factories;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Intern;
use App\Models\SupervisorNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisorNote>
 */
class SupervisorNoteFactory extends Factory
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
            'note_date' => $this->faker->date(),
            'observation' => $this->faker->paragraph(),
            'category' => NoteCategory::GENERAL,
            'visibility' => NoteVisibility::INTERNAL,
        ];
    }
}
