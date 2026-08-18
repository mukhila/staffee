<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('cycle_type', ['annual', 'half_yearly', 'quarterly', 'probation', 'adhoc']);
            $table->string('fiscal_year', 9)->nullable();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->date('submission_deadline');
            $table->date('calibration_deadline')->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_cycles');
    }
};
