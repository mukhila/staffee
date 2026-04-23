<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resignation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('submitted_date');
            $table->date('requested_last_date');        // last date employee wants to work
            $table->date('official_last_date')->nullable();   // HR-confirmed last date after notice calc
            $table->integer('notice_period_days');       // snapshot from profile at time of submission

            $table->enum('resignation_type', [
                'voluntary', 'mutual_separation',
            ])->default('voluntary');

            $table->text('reason');
            $table->boolean('notice_waived')->default(false);   // true if notice period is waived
            $table->text('waiver_reason')->nullable();

            // Step 1: manager acceptance
            $table->enum('status', [
                'pending',
                'manager_reviewing',
                'manager_accepted',
                'manager_rejected',
                'hr_reviewing',
                'approved',             // HR confirmed + dates locked
                'rejected',
                'withdrawn',
            ])->default('pending');

            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_reviewed_at')->nullable();
            $table->enum('manager_decision', ['accepted', 'rejected'])->nullable();
            $table->text('manager_notes')->nullable();

            // Step 2: HR processing
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->text('hr_notes')->nullable();

            $table->text('withdrawal_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resignation_requests');
    }
};
