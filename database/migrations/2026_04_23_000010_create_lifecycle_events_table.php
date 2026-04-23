<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This is the canonical timeline for every HR event on an employee.
// Detail tables (promotion_requests, termination_requests, etc.) always
// write a row here so the profile page can show one unified history.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', [
                'joined',
                'probation_completed',
                'promotion',
                'demotion',
                'transfer',
                'role_change',
                'salary_revision',
                'warning',
                'suspension',
                'reinstatement',
                'resignation_submitted',
                'resignation_accepted',
                'notice_period_waived',
                'termination',
                'contract_renewed',
                'contract_ended',
            ]);
            $table->string('title');                    // human-readable: "Promoted to Senior Dev"
            $table->text('description')->nullable();

            $table->date('effective_date');

            // Snapshot of what changed — stored even if the related table is later deleted
            $table->string('old_role')->nullable();
            $table->string('new_role')->nullable();
            $table->foreignId('old_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('new_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('old_designation')->nullable();
            $table->string('new_designation')->nullable();
            $table->decimal('old_salary', 12, 2)->nullable();
            $table->decimal('new_salary', 12, 2)->nullable();

            // Back-link to the originating detail table
            $table->string('reference_type')->nullable();   // 'promotion_requests', 'warning_records'
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('performed_by')->constrained('users');

            // Sensitive events (warnings, suspensions) hidden from the employee's own view
            $table->boolean('is_sensitive')->default(false);

            // Catch-all for extra context without adding columns
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'event_type']);
            $table->index(['user_id', 'effective_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycle_events');
    }
};
