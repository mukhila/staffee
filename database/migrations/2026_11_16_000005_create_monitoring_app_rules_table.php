<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_app_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pattern', 255);              // text to look for, case-insensitive
            $table->string('match_type', 10)->default('app'); // app = application name, title = window title (websites)
            $table->string('category', 20);              // productive, neutral, unproductive
            $table->timestamps();

            $table->unique(['match_type', 'pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_app_rules');
    }
};
