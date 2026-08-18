<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->string('applicant_name', 150);
            $table->string('applicant_email', 180);
            $table->string('applicant_phone', 30)->nullable();
            $table->string('resume_path', 400)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('source', 80)->nullable();
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'new', 'screening', 'interview_scheduled', 'interviewed',
                'offer_sent', 'hired', 'rejected', 'withdrawn',
            ])->default('new');
            $table->tinyInteger('rating')->nullable();
            $table->text('hr_notes')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
