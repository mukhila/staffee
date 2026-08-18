<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->foreignId('payroll_calendar_id')->nullable()->constrained('payroll_calendars')->nullOnDelete();
            $table->string('due_period', 30);
            $table->decimal('scheduled_amount', 18, 6);
            $table->decimal('recovered_amount', 18, 6)->default(0);
            $table->enum('status', ['pending', 'processed', 'skipped', 'waived'])->default('pending');
            $table->foreignId('payroll_slip_line_id')->nullable()->constrained('payroll_slip_lines')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_installments');
    }
};
