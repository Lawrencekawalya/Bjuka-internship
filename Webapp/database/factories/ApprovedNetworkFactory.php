<?php

namespace Database\Factories;

use App\Models\ApprovedNetwork;
use App\Models\InternshipBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovedNetwork>
 */
class ApprovedNetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => InternshipBatch::factory(),
            'name' => 'Main Office WiFi',
            'ssid' => 'BJUKA_Internal',
            'bssid' => '00:11:22:33:44:55',
        ];
    }
}
