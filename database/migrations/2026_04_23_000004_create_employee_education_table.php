<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('institution_name');
            $table->string('degree');              // Bachelor of Science, MBA, etc.
            $table->string('field_of_study')->nullable();
            $table->smallInteger('start_year')->nullable();
            $table->smallInteger('end_year')->nullable();   // null if currently enrolled
            $table->boolean('is_current')->default(false);
            $table->string('grade_gpa')->nullable();        // '3.8 / 4.0', 'First Class'
            $table->text('activities')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_education');
    }
};
