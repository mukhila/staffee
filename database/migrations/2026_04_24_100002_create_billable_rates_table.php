<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billable_rates', function (Blueprint $table) {
            $table->id();

            // NULL means "applies to all" for that dimension
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            // Specificity: user_project > project > user > global
            $table->enum('rate_type', ['global', 'user', 'project', 'user_project'])
                  ->default('global');

            $table->decimal('hourly_rate', 10, 2);
            $table->char('currency', 3)->default('USD');

            // Inclusive date range. effective_to = null means "currently active"
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'rate_type', 'effective_from']);
            $table->index(['project_id', 'rate_type', 'effective_from']);
            $table->index(['rate_type', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billable_rates');
    }
};
