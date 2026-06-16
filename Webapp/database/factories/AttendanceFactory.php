<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Intern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'date' => $this->faker->date(),
            'status' => AttendanceStatus::PRESENT,
            'wifi_ssid' => 'BJUKA_Internal',
            'wifi_bssid' => '00:11:22:33:44:55',
        ];
    }
}
