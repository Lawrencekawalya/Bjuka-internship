<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_program_weeks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->constrained('internship_batches')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('objectives');
            $table->text('topics');
            $table->text('activities');
            $table->timestamps();

            $table->unique(['batch_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_program_weeks');
    }
};
