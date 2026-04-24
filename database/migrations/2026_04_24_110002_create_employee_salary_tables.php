<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop in dependency order in case a previous partial run left these tables
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('employee_salary_structures');

        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_structure_id')
                ->nullable()
                ->constrained('payroll_grade_structures')
                ->nullOnDelete();
            $table->enum('pay_frequency', ['monthly', 'bi_weekly'])->default('monthly');
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('annual_ctc', 18, 6)->nullable();
            $table->decimal('monthly_base_salary', 18, 6);
            $table->unsignedSmallInteger('standard_work_days')->default(30);
            $table->decimal('standard_work_hours', 8, 4)->default(8);
            $table->boolean('overtime_eligible')->default(false);
            $table->foreignId('tax_regime_id')->nullable()->constrained('tax_regimes')->nullOnDelete();
            $table->string('professional_tax_state_code', 10)->nullable();
            $table->boolean('pf_enabled')->default(true);
            $table->boolean('esi_enabled')->default(false);
            $table->enum('status', ['draft', 'pending_approval', 'active', 'superseded', 'inactive'])->default('draft');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('source_revision_id')->nullable()->constrained('salary_revisions')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'effective_from']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained('employee_salary_structures')->cascadeOnDelete();
            $table->foreignId('component_definition_id')
                ->constrained('payroll_component_definitions')
                ->cascadeOnDelete();
            $table->enum('amount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('amount', 18, 6)->nullable();
            $table->decimal('percentage', 10, 6)->nullable();
            $table->foreignId('basis_component_definition_id')
                ->nullable()
                ->constrained('payroll_component_definitions')
                ->nullOnDelete();
            $table->decimal('min_amount', 18, 6)->nullable();
            $table->decimal('max_amount', 18, 6)->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['salary_structure_id', 'component_definition_id'], 'esc_structure_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('employee_salary_structures');
    }
};
