<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accrual_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_balance_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('days_accrued', 5, 2);
            $table->string('accrual_method', 20); // monthly / quarterly / annual
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leave_type_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_accrual_logs');
    }
};
