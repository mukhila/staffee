<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'resume', 'id_proof', 'offer_letter', 'contract', 'increment_letter',
                'relieving_letter', 'experience_letter', 'nda', 'appraisal', 'other',
            ]);
            $table->string('name');                     // display label
            $table->string('file_path');
            $table->unsignedInteger('file_size')->nullable();   // bytes
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
