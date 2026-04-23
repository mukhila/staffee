<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            // fixed | rotating | flexible | night | hybrid
            $table->string('shift_type')->default('fixed');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->unsignedSmallInteger('break_duration_minutes')->default(60);
            // Tolerances
            $table->unsignedSmallInteger('grace_in_minutes')->default(10);
            $table->unsignedSmallInteger('grace_out_minutes')->default(10);
            $table->unsignedSmallInteger('overtime_threshold_minutes')->default(30);
            // Thresholds
            $table->unsignedSmallInteger('min_hours_for_full_day')->default(8);
            $table->unsignedSmallInteger('half_day_threshold_hours')->default(4);
            // Flexible-shift window (when shift_type = flexible)
            $table->time('flexible_window_start')->nullable();
            $table->time('flexible_window_end')->nullable();
            $table->unsignedSmallInteger('flexible_duration_hours')->nullable();
            // Applicable days (JSON array: ["Mon","Tue","Wed","Thu","Fri"])
            $table->json('working_days')->nullable();
            // Display / meta
            $table->string('color', 7)->default('#3B82F6');
            $table->string('timezone')->default('Asia/Kolkata');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
