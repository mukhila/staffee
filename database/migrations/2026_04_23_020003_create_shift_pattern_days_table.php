<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_pattern_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')->constrained('shift_patterns')->onDelete('cascade');
            $table->unsignedTinyInteger('day_number');
            $table->boolean('is_working_day')->default(true);
            // Optional per-day overrides for rotating shifts with variable hours
            $table->time('override_start_time')->nullable();
            $table->time('override_end_time')->nullable();
            $table->timestamps();

            $table->unique(['pattern_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_pattern_days');
    }
};
