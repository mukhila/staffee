<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 80)->nullable();
            $table->string('target_metric', 150)->nullable();
            $table->text('achievement_notes')->nullable();
            $table->decimal('weightage', 5, 2)->default(0);
            $table->decimal('self_rating', 4, 2)->nullable();
            $table->decimal('reviewer_rating', 4, 2)->nullable();
            $table->enum('status', [
                'not_started',
                'in_progress',
                'achieved',
                'partially_achieved',
                'not_achieved',
            ])->default('not_started');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_goals');
    }
};
