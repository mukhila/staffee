<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tax_regime_id')->constrained('tax_regimes');
            $table->string('fiscal_year', 9);
            $table->enum('declaration_status', ['draft', 'submitted', 'verified', 'locked'])->default('draft');
            $table->json('declared_amounts');
            $table->json('proof_status')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fiscal_year']);
            $table->index(['declaration_status', 'fiscal_year']);
        });

        Schema::create('tax_declaration_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_declaration_id')->constrained('employee_tax_declarations')->cascadeOnDelete();
            $table->string('section', 20);
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['tax_declaration_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_declaration_proofs');
        Schema::dropIfExists('employee_tax_declarations');
    }
};
