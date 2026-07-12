<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->string('status')->default('generating');
            $table->json('content')->nullable();
            $table->string('docx_path')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['intern_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_reports');
    }
};
