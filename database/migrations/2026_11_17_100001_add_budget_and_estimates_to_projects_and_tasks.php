<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget_amount', 18, 6)->nullable();   // spending limit for labour cost
            $table->char('budget_currency', 3)->nullable();
            $table->decimal('budget_hours', 10, 2)->nullable();    // hours the project may use
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('estimated_hours', 8, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget_amount', 'budget_currency', 'budget_hours']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });
    }
};
