<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_grade_structures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency_code', 3)->default('Ruppee');
            $table->enum('pay_frequency', ['monthly', 'bi_weekly'])->default('monthly');
            $table->decimal('min_ctc', 18, 6)->nullable();
            $table->decimal('max_ctc', 18, 6)->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['code', 'effective_from']);
            $table->index(['department_id', 'status']);
        });

        Schema::create('payroll_component_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 150);
            $table->enum('category', ['earning', 'deduction', 'employer_contribution', 'information']);
            $table->enum('component_type', [
                'basic', 'allowance', 'reimbursement', 'statutory', 'tax', 'loan', 'insurance',
                'adjustment', 'benefit', 'encashment', 'gratuity', 'bonus', 'other',
            ]);
            $table->enum('calculation_method', [
                'fixed', 'percentage_of_component', 'percentage_of_gross', 'percentage_of_taxable_gross',
                'formula', 'slab', 'per_day', 'per_hour', 'manual_input',
            ])->default('fixed');
            $table->boolean('taxable')->default(false);
            $table->boolean('pro_ratable')->default(true);
            $table->boolean('affects_gross')->default(false);
            $table->boolean('affects_net')->default(true);
            $table->boolean('employer_only')->default(false);
            $table->boolean('arrear_eligible')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedTinyInteger('rounding_scale')->default(2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->text('formula_expression')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_component_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_definition_id')
                ->constrained('payroll_component_definitions')
                ->cascadeOnDelete();
           $table->foreignId('basis_component_definition_id')
    ->constrained('payroll_component_definitions', 'id', 'pcd_basis_comp_def_fk')
    ->cascadeOnDelete();
            $table->decimal('percentage', 10, 6);
            $table->decimal('cap_amount', 18, 6)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['component_definition_id', 'effective_from'], 'pcd_component_effective_idx');
        });

        Schema::create('statutory_deduction_rules', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->default('IN');
            $table->string('state_code', 10)->nullable();
            $table->enum('rule_type', ['pf', 'esi', 'professional_tax', 'income_tax_support']);
            $table->decimal('employee_rate', 10, 6)->nullable();
            $table->decimal('employer_rate', 10, 6)->nullable();
            $table->decimal('wage_ceiling', 18, 6)->nullable();
            $table->decimal('min_wage', 18, 6)->nullable();
            $table->decimal('max_amount', 18, 6)->nullable();
            $table->json('slab_json')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['country_code', 'state_code', 'rule_type'], 'sdr_country_state_type_idx');
        });

        Schema::create('tax_regimes', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->default('IN');
            $table->string('fiscal_year', 9);
            $table->string('regime_code', 30);
            $table->string('name', 120);
            $table->decimal('standard_deduction', 18, 6)->default(0);
            $table->decimal('rebate_amount', 18, 6)->default(0);
            $table->json('surcharge_json')->nullable();
            $table->decimal('cess_percent', 10, 6)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'fiscal_year', 'regime_code']);
        });

        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_regime_id')->constrained('tax_regimes')->cascadeOnDelete();
            $table->decimal('income_from', 18, 6);
            $table->decimal('income_to', 18, 6)->nullable();
            $table->decimal('rate_percent', 10, 6);
            $table->decimal('fixed_tax_amount', 18, 6)->default(0);
            $table->boolean('rebate_eligible')->default(false);
            $table->timestamps();

            $table->index(['tax_regime_id', 'income_from'], 'tax_brackets_regime_income_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('tax_regimes');
        Schema::dropIfExists('statutory_deduction_rules');
        Schema::dropIfExists('payroll_component_dependencies');
        Schema::dropIfExists('payroll_component_definitions');
        Schema::dropIfExists('payroll_grade_structures');
    }
};
