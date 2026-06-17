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
        Schema::table('internship_batches', function (Blueprint $table) {
            $table->string('batch_code')->unique()->after('id');
            $table->integer('capacity')->default(0)->after('expected_working_days');
            $table->foreignId('coordinator_id')->nullable()->after('capacity')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internship_batches', function (Blueprint $table) {
            $table->dropForeign(['coordinator_id']);
            $table->dropColumn(['batch_code', 'capacity', 'coordinator_id']);
        });
    }
};
