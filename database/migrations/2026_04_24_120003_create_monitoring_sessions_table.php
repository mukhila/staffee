<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->string('os_info', 255)->nullable();
            $table->string('agent_version', 20)->nullable();
            $table->enum('status', ['active', 'ended', 'expired'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status'], 'mon_sessions_user_status_idx');
            $table->index('last_heartbeat_at', 'mon_sessions_heartbeat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_sessions');
    }
};
