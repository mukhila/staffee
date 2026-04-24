<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_calendar_id')->nullable()->constrained('payroll_calendars')->nullOnDelete();
            $table->foreignId('component_definition_id')
                ->constrained('payroll_component_definitions')
                ->cascadeOnDelete();
            $table->enum('adjustment_type', ['earning', 'deduction']);
            $table->decimal('amount', 18, 6);
            $table->decimal('quantity', 12, 4)->nullable();
            $table->text('reason');
            $table->enum('recurrence', ['one_time', 'repeat_until', 'fixed_installments'])->default('one_time');
            $table->string('start_period', 30)->nullable();
            $table->string('end_period', 30)->nullable();
            $table->unsignedInteger('remaining_installments')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'processed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('payroll_input_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('attendance_summary')->nullable();
            $table->json('leave_summary')->nullable();
            $table->json('time_summary')->nullable();
            $table->json('deduction_summary')->nullable();
            $table->json('salary_structure_snapshot')->nullable();
            $table->json('tax_context_snapshot')->nullable();
            $table->string('snapshot_hash', 64);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'user_id']);
        });

        Schema::create('payroll_calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_slip_id')->nullable()->constrained('payroll_slips')->nullOnDelete();
            $table->foreignId('payroll_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('stage', [
                'input_collection', 'earning_calc', 'deduction_calc', 'tax_calc',
                'net_calc', 'approval', 'publish', 'payment',
            ]);
            $table->string('action', 100);
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->text('formula_used')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();
        });

        Schema::create('payroll_audits', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 100);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'payroll_audits_auditable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_audits');
        Schema::dropIfExists('payroll_calculation_logs');
        Schema::dropIfExists('payroll_input_snapshots');
        Schema::dropIfExists('payroll_adjustments');
    }
};
