<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('old_salary', 12, 2)->nullable();  // null for the first (joining) entry
            $table->decimal('new_salary', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('revision_type', [
                'joining', 'promotion', 'annual_increment', 'demotion', 'correction', 'other',
            ]);
            $table->decimal('percentage_change', 6, 2)->nullable();  // calculated; stored for history
            $table->text('reason')->nullable();
            // Polymorphic back-link to the event that triggered this revision
            $table->string('reference_type')->nullable();   // 'promotion_requests', etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'effective_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revisions');
    }
};
