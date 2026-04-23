<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained();
            $table->date('date');
            // late_arrival | early_departure | absent | overtime | half_day | no_check_out | unscheduled_work
            $table->string('exception_type');
            $table->dateTime('expected_start')->nullable();
            $table->dateTime('expected_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->smallInteger('deviation_minutes')->default(0);
            $table->smallInteger('overtime_minutes')->default(0);
            $table->text('reason')->nullable();
            // pending | approved | rejected | auto_approved
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->boolean('is_paid_overtime')->default(false);
            $table->timestamps();

            $table->unique(['attendance_id', 'exception_type'], 'unique_attendance_exception');
            $table->index(['user_id', 'date', 'exception_type']);
            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_exceptions');
    }
};
