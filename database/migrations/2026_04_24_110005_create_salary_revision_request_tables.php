<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_salary_structure_id')
                ->nullable()
                ->constrained('employee_salary_structures')
                ->nullOnDelete();
            $table->foreignId('proposed_grade_structure_id')
                ->nullable()
                ->constrained('payroll_grade_structures')
                ->nullOnDelete();
            $table->enum('revision_type', [
                'joining', 'promotion', 'annual_increment', 'market_adjustment',
                'correction', 'demotion', 'transfer', 'other',
            ]);
            $table->date('effective_date');
            $table->date('retroactive_from')->nullable();
            $table->string('proposed_currency_code', 3)->default('USD');
            $table->decimal('proposed_base_salary', 18, 6);
            $table->decimal('old_gross_monthly', 18, 6)->nullable();
            $table->decimal('new_gross_monthly', 18, 6);
            $table->json('impact_summary')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', [
                'draft', 'pending_manager', 'pending_hr', 'pending_finance',
                'approved', 'rejected', 'implemented',
            ])->default('draft');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('salary_revision_request_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_request_id')->constrained('salary_revision_requests')->cascadeOnDelete();
            $table->foreignId('component_definition_id')
                ->constrained('payroll_component_definitions')
                ->cascadeOnDelete();
            $table->decimal('old_amount', 18, 6)->nullable();
            $table->decimal('new_amount', 18, 6)->nullable();
            $table->decimal('old_percentage', 10, 6)->nullable();
            $table->decimal('new_percentage', 10, 6)->nullable();
            $table->enum('change_type', ['added', 'updated', 'removed', 'unchanged'])->default('updated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revision_request_components');
        Schema::dropIfExists('salary_revision_requests');
    }
};
