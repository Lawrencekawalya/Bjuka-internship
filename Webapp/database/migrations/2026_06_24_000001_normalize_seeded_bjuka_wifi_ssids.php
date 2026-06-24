<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $networks = DB::table('approved_networks')
            ->where('ssid', 'like', 'BJUKA_WIFI%')
            ->select(['id', 'ssid'])
            ->get();

        foreach ($networks as $network) {
            if (! str_starts_with($network->ssid, 'BJUKA_WIFI_')) {
                continue;
            }

            DB::table('approved_networks')
                ->where('id', $network->id)
                ->update(['ssid' => 'BJUKA_WIFI']);
        }
    }

    public function down(): void
    {
        //
    }
};
