<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            // national | regional | company
            $table->string('holiday_type')->default('national');
            // If true, repeats on same month-day every year
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_holidays');
    }
};
