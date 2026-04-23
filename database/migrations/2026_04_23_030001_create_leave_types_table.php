<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->enum('category', [
                'paid_annual', 'paid_casual', 'paid_medical',
                'unpaid', 'maternity', 'paternity',
                'sick', 'special', 'custom',
            ])->default('custom');
            $table->string('color', 7)->default('#6366f1'); // hex color
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->unsignedSmallInteger('max_days_per_year')->nullable(); // null = unlimited
            $table->boolean('allow_half_day')->default(false);
            $table->boolean('requires_document')->default(false); // e.g. medical cert
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
