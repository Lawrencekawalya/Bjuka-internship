<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_batches', function (Blueprint $table) {
            $table->longText('report_format_text')->nullable()->after('description');
            $table->string('report_format_path')->nullable()->after('report_format_text');
            $table->string('report_format_original_name')->nullable()->after('report_format_path');
        });
    }

    public function down(): void
    {
        Schema::table('internship_batches', function (Blueprint $table) {
            $table->dropColumn([
                'report_format_text',
                'report_format_path',
                'report_format_original_name',
            ]);
        });
    }
};
