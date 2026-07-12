<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_report_generation_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_id')->unique()->constrained('interns')->cascadeOnDelete();
            $table->unsignedTinyInteger('generation_count')->default(0);
            $table->unsignedTinyInteger('generation_limit')->default(3);
            $table->timestamp('reset_requested_at')->nullable();
            $table->timestamp('reset_approved_at')->nullable();
            $table->boolean('reset_used')->default(false);
            $table->timestamp('permanently_locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_report_generation_quotas');
    }
};
