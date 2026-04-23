<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warning_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users');

            $table->enum('warning_type', [
                'verbal', 'written', 'final_written', 'suspension', 'pip',
            ]);
            $table->text('reason');
            $table->date('incident_date');
            $table->text('action_required')->nullable();
            $table->date('response_deadline')->nullable();

            // Employee response tracking
            $table->text('employee_response')->nullable();
            $table->timestamp('employee_responded_at')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();

            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'warning_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_records');
    }
};
