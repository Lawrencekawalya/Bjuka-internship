<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $batches = DB::table('internship_batches')
            ->select(['id', 'name'])
            ->get();

        foreach ($batches as $batch) {
            $hasBjukaWifi = DB::table('approved_networks')
                ->where('batch_id', $batch->id)
                ->whereRaw('lower(trim(ssid)) = ?', ['bjuka_wifi'])
                ->exists();

            if ($hasBjukaWifi) {
                continue;
            }

            DB::table('approved_networks')->insert([
                'id' => (string) Str::uuid(),
                'batch_id' => $batch->id,
                'name' => $batch->name.' Office WiFi',
                'ssid' => 'BJUKA_WIFI',
                'bssid' => 'any',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
