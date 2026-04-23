<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termination_id')->unique()->constrained('termination_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('last_working_date');

            // ── Earnings ──────────────────────────────────────────────────
            $table->decimal('basic_salary', 12, 2);            // monthly gross at termination
            $table->unsignedInteger('pending_salary_days');    // days worked in final month
            $table->decimal('pending_salary_amount', 12, 2);

            $table->decimal('leave_encashment_days', 6, 2)->default(0);
            $table->decimal('leave_encashment_amount', 12, 2)->default(0);

            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('gratuity', 12, 2)->default(0);

            // Flexible additional earnings [{label, amount}]
            $table->json('other_earnings')->nullable();
            $table->decimal('total_earnings', 12, 2);

            // ── Deductions ────────────────────────────────────────────────
            $table->decimal('pending_advances', 12, 2)->default(0);
            $table->unsignedInteger('notice_shortfall_days')->default(0);
            $table->decimal('notice_shortfall_deduction', 12, 2)->default(0);

            // Flexible additional deductions [{label, amount}]
            $table->json('other_deductions')->nullable();
            $table->decimal('total_deductions', 12, 2);

            // ── Net ───────────────────────────────────────────────────────
            $table->decimal('net_payable', 12, 2);
            $table->string('currency', 3)->default('USD');

            // Workflow
            $table->enum('status', [
                'draft', 'pending_approval', 'approved', 'paid',
            ])->default('draft');

            $table->foreignId('calculated_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_mode')->nullable();         // bank_transfer, cheque, cash
            $table->string('payment_reference')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_settlements');
    }
};
