<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('current_shift_id')->constrained('shifts');
            $table->foreignId('requested_shift_id')->constrained('shifts');
            // Optional: swap with another employee
            $table->foreignId('swap_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date');
            $table->text('reason');
            // pending | approved | rejected | cancelled
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index(['status', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};
