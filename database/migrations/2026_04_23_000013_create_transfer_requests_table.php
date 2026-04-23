<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');

            $table->foreignId('from_department_id')->constrained('departments');
            $table->foreignId('to_department_id')->constrained('departments');
            $table->string('from_role')->nullable();    // role slug
            $table->string('to_role')->nullable();
            $table->foreignId('from_reporting_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_reporting_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_designation')->nullable();
            $table->string('to_designation')->nullable();

            $table->date('effective_date');
            $table->text('reason');

            $table->enum('status', [
                'pending', 'approved', 'rejected', 'completed', 'cancelled',
            ])->default('pending');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
