<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'experience_letter',
                'salary_certificate',
                'noc',
                'appointment_letter',
                'promotion_letter',
                'custom',
            ]);
            $table->string('custom_type', 120)->nullable();
            $table->text('purpose')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('document_path', 400)->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
