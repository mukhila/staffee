<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // who is being promoted
            $table->foreignId('proposed_by')->constrained('users');           // manager/HR initiating

            // What changes
            $table->string('current_role');         // role slug snapshot
            $table->string('proposed_role');
            $table->foreignId('current_department_id')->constrained('departments');
            $table->foreignId('proposed_department_id')->constrained('departments');
            $table->string('current_designation')->nullable();
            $table->string('proposed_designation')->nullable();
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->decimal('proposed_salary', 12, 2)->nullable();

            $table->date('effective_date');
            $table->text('reason');

            $table->enum('status', [
                'draft',
                'pending_manager',
                'manager_approved',
                'manager_rejected',
                'pending_hr',
                'hr_approved',
                'hr_rejected',
                'pending_finance',
                'finance_approved',
                'finance_rejected',
                'approved',             // all sign-offs done; changes applied
                'rejected',
                'withdrawn',
            ])->default('draft');

            // Approval chain (3 steps: manager → HR → finance)
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable();
            $table->text('manager_notes')->nullable();

            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();
            $table->text('hr_notes')->nullable();

            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();
            $table->text('finance_notes')->nullable();

            $table->timestamp('announced_at')->nullable();   // when team was notified
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_requests');
    }
};
