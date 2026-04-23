<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termination_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users');

            $table->enum('termination_type', [
                'voluntary_resignation',    // triggered by an approved resignation
                'involuntary_dismissal',    // performance / misconduct
                'layoff',
                'end_of_contract',
                'retirement',
                'mutual_separation',
                'abandonment',              // no-show / absconding
            ]);

            $table->text('reason');
            $table->date('last_working_date');

            // Link to resignation if this termination follows one
            $table->foreignId('resignation_id')
                  ->nullable()
                  ->constrained('resignation_requests')
                  ->nullOnDelete();

            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'processing',       // exit checklist underway
                'settlement_pending',
                'settlement_approved',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->enum('settlement_status', [
                'not_started', 'calculating', 'pending_approval', 'approved', 'paid',
            ])->default('not_started');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_requests');
    }
};
