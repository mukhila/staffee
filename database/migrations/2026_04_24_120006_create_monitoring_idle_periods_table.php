<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_idle_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('monitoring_sessions')->cascadeOnDelete();
            $table->timestamp('idle_start');
            $table->timestamp('idle_end')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'idle_start'], 'mon_idle_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_idle_periods');
    }
};
