<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 30)->nullable();
            $table->enum('pay_frequency', ['monthly', 'bi_weekly'])->default('monthly');
            $table->string('period_code', 30);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->date('attendance_cutoff_date');
            $table->date('timesheet_cutoff_date');
            $table->date('leave_cutoff_date');
            $table->enum('status', ['draft', 'open', 'locked', 'processed', 'paid'])->default('draft');
            $table->timestamps();

            $table->unique(['period_code', 'pay_frequency']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_calendar_id')->constrained('payroll_calendars')->cascadeOnDelete();
            $table->enum('run_type', ['regular', 'supplementary', 'adjustment', 'full_final'])->default('regular');
            $table->string('currency_code', 3)->default('INR');
            $table->enum('employee_scope_type', ['all', 'department', 'employee_list'])->default('all');
            $table->json('employee_scope')->nullable();
            $table->enum('status', [
                'draft', 'processing', 'collecting_inputs', 'calculating',
                'completed', 'pending_approval', 'approved', 'posted', 'paid', 'cancelled',
            ])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->string('input_snapshot_hash', 64)->nullable();
            $table->json('totals_json')->nullable();
            $table->json('error_log')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['payroll_calendar_id', 'status']);
        });

        Schema::create('payroll_run_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_structure_id')
                ->nullable()
                ->constrained('employee_salary_structures')
                ->nullOnDelete();
            $table->string('employment_status_snapshot', 50)->nullable();
            $table->enum('inclusion_status', ['included', 'excluded', 'hold'])->default('included');
            $table->text('exclusion_reason')->nullable();
            $table->json('source_summary')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'user_id']);
        });

        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_calendar_id')->constrained('payroll_calendars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_structure_id')
                ->nullable()
                ->constrained('employee_salary_structures')
                ->nullOnDelete();
            $table->string('currency_code', 3)->default('INR');
            $table->enum('pay_frequency', ['monthly', 'bi_weekly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('payable_days', 8, 4)->default(0);
            $table->decimal('worked_days', 8, 4)->default(0);
            $table->decimal('paid_leave_days', 8, 4)->default(0);
            $table->decimal('unpaid_leave_days', 8, 4)->default(0);
            $table->decimal('overtime_hours', 10, 4)->default(0);
            $table->decimal('gross_earnings', 18, 6)->default(0);
            $table->decimal('total_deductions', 18, 6)->default(0);
            $table->decimal('employer_contributions', 18, 6)->default(0);
            $table->decimal('taxable_income', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('net_pay', 18, 6)->default(0);
            $table->decimal('ytd_gross', 18, 6)->default(0);
            $table->decimal('ytd_tax', 18, 6)->default(0);
            $table->decimal('ytd_net', 18, 6)->default(0);
            $table->enum('status', ['draft', 'approved', 'published', 'paid', 'cancelled'])->default('draft');
            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_reference')->nullable();
            $table->unsignedInteger('calculation_version')->default(1);
            $table->json('snapshot_json');
            $table->timestamps();

            $table->index(['user_id', 'period_start', 'period_end']);
            $table->index(['payroll_run_id', 'status']);
        });

        Schema::create('payroll_slip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_slip_id')->constrained('payroll_slips')->cascadeOnDelete();
            $table->foreignId('component_definition_id')
                ->nullable()
                ->constrained('payroll_component_definitions')
                ->nullOnDelete();
            $table->string('line_code', 60);
            $table->string('line_name', 150);
            $table->enum('line_category', ['earning', 'deduction', 'employer_contribution', 'information']);
            $table->enum('source_type', [
                'salary_structure', 'attendance', 'leave', 'time_tracking', 'manual_adjustment',
                'tax_engine', 'settlement', 'arrear', 'statutory',
            ]);
            $table->string('source_reference_type')->nullable();
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->string('calculation_basis', 255)->nullable();
            $table->decimal('quantity', 12, 4)->nullable();
            $table->decimal('rate', 18, 6)->nullable();
            $table->decimal('amount', 18, 6);
            $table->decimal('taxable_amount', 18, 6)->default(0);
            $table->boolean('is_ytd_included')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payroll_slip_id', 'line_category']);
            $table->index(['source_reference_type', 'source_reference_id'], 'psl_source_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_slip_lines');
        Schema::dropIfExists('payroll_slips');
        Schema::dropIfExists('payroll_run_employees');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_calendars');
    }
};
