<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('provider', 150)->nullable();
            $table->string('category', 80)->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->decimal('cost', 18, 6)->default(0);
            $table->boolean('is_mandatory')->default(false);
            $table->enum('status', ['draft','active','archived'])->default('active');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_courses');
    }
};
