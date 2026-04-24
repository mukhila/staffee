<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('monitoring_sessions')->cascadeOnDelete();
            $table->timestamp('captured_at');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('active_window_title', 500)->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'captured_at'], 'mon_screenshots_user_time_idx');
            $table->index(['session_id', 'captured_at'], 'mon_screenshots_session_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_screenshots');
    }
};
