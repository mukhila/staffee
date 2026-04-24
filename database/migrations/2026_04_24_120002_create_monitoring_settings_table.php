<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_settings', function (Blueprint $table) {
            $table->id();
            // null user_id = global default; set user_id = per-employee override
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('screenshot_enabled')->default(true);
            $table->unsignedSmallInteger('screenshot_interval_seconds')->default(300);
            $table->boolean('activity_tracking_enabled')->default(true);
            $table->unsignedSmallInteger('idle_threshold_seconds')->default(300);
            $table->boolean('working_hours_only')->default(false);
            $table->time('work_start_time')->default('09:00:00');
            $table->time('work_end_time')->default('18:00:00');
            $table->boolean('notify_employee')->default(false);
            $table->timestamps();

            $table->unique('user_id', 'mon_settings_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_settings');
    }
};
