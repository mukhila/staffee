<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');

            $table->decimal('opening_balance', 6, 1)->default(0);    // brought forward from previous year
            $table->decimal('carry_forward_days', 6, 1)->default(0); // carry-forward credited for this year
            $table->decimal('accrued_days', 6, 1)->default(0);       // accrued during the year
            $table->decimal('used_days', 6, 1)->default(0);          // consumed by approved leaves
            $table->decimal('pending_days', 6, 1)->default(0);       // in pending/manager_approved state

            // Snapshot: recomputed on every balance change
            $table->decimal('available_balance', 6, 1)
                ->storedAs('opening_balance + carry_forward_days + accrued_days - used_days');

            $table->date('last_accrual_date')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'year']);
            $table->index(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
