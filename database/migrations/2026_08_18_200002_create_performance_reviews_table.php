<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('performance_cycles')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->decimal('overall_rating', 4, 2)->nullable();
            $table->text('overall_comments')->nullable();
            $table->decimal('self_rating', 4, 2)->nullable();
            $table->text('self_comments')->nullable();
            $table->enum('status', [
                'pending',
                'self_submitted',
                'manager_reviewing',
                'hr_calibrated',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('acknowledged_by_employee')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
