<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('loan_type', 80);
            $table->decimal('principal_amount', 18, 6);
            $table->date('issued_date');
            $table->string('recovery_start_period', 30);
            $table->decimal('installment_amount', 18, 6);
            $table->unsignedInteger('total_installments');
            $table->decimal('remaining_balance', 18, 6);
            $table->enum('status', ['active', 'completed', 'cancelled', 'hold'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
