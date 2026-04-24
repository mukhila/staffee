<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('monitoring_sessions')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->unsignedSmallInteger('duration_seconds')->default(60);
            $table->string('active_app_name', 255)->nullable();
            $table->string('active_window_title', 500)->nullable();
            $table->unsignedInteger('keyboard_events')->default(0);
            $table->unsignedInteger('mouse_events')->default(0);
            $table->unsignedInteger('mouse_distance_px')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'recorded_at'], 'mon_activity_user_time_idx');
            $table->index(['session_id', 'recorded_at'], 'mon_activity_session_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_activity_logs');
    }
};
