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
        Schema::table('daily_status_reports', function (Blueprint $table) {
            $table->string('task_name')->after('report_date');
            $table->text('description')->after('task_name');
            $table->time('start_time')->after('description');
            $table->time('end_time')->after('start_time');
            $table->string('status')->default('pending')->after('end_time');
            $table->dropColumn('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_status_reports', function (Blueprint $table) {
            $table->text('content');
            $table->dropColumn(['task_name', 'description', 'start_time', 'end_time', 'status']);
        });
    }
};
