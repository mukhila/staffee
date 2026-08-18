<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 6);
            $table->string('currency', 3)->default('INR');
            $table->date('expense_date');
            $table->string('receipt_path', 400)->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'paid'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claims');
    }
};
