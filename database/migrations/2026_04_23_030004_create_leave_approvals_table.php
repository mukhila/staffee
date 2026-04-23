<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Multi-level approval audit trail — one row per decision action
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('level'); // 1=manager, 2=hr, 3=finance
            $table->enum('action', ['approved', 'rejected', 'forwarded', 'cancelled']);
            $table->text('notes')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['leave_request_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
