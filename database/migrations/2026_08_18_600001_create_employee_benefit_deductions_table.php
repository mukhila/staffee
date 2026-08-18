<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_benefit_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('benefit_name', 150);
            $table->enum('benefit_type', ['insurance', 'transport', 'food_voucher', 'provident_fund', 'other']);
            $table->decimal('amount', 18, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('frequency', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->enum('status', ['active', 'paused', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_benefit_deductions');
    }
};
