<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            // Scope: null = all employees; set one to narrow scope
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_level', 50)->nullable(); // junior / senior / manager / all

            // Balance rules
            $table->decimal('max_days_per_year', 5, 1)->default(0);
            $table->decimal('carry_forward_days', 5, 1)->default(0);
            $table->unsignedTinyInteger('carry_forward_expiry_months')->default(3); // expires after N months into new year

            // Accrual
            $table->enum('accrual_method', ['immediate', 'monthly', 'quarterly', 'annual'])->default('annual');
            $table->decimal('accrual_amount', 5, 2)->default(0); // days credited per period

            // Eligibility
            $table->unsignedSmallInteger('vesting_period_months')->default(0); // probation lockout
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();

            // Approval chain
            $table->boolean('requires_manager_approval')->default(true);
            $table->boolean('requires_hr_approval')->default(false);
            $table->unsignedTinyInteger('auto_approve_days')->nullable(); // auto-approve if <= N days

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
