<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('check_in_device_time')->nullable();
            $table->timestamp('check_in_server_time')->nullable();
            $table->timestamp('check_out_device_time')->nullable();
            $table->timestamp('check_out_server_time')->nullable();
            $table->integer('work_duration_minutes')->nullable();
            $table->string('status'); // Enum: AttendanceStatus
            $table->string('wifi_ssid')->nullable();
            $table->string('wifi_bssid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
