<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship']);
            $table->string('location', 150)->nullable();
            $table->decimal('salary_min', 18, 6)->nullable();
            $table->decimal('salary_max', 18, 6)->nullable();
            $table->unsignedSmallInteger('openings')->default(1);
            $table->enum('status', ['draft', 'open', 'closed', 'on_hold'])->default('draft');
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
