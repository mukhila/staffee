<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuing_organization');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();    // null = does not expire
            $table->string('credential_id')->nullable();
            $table->string('credential_url')->nullable();
            $table->string('file_path')->nullable();    // uploaded certificate scan
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'expiry_date']);  // expiry monitoring queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_certifications');
    }
};
