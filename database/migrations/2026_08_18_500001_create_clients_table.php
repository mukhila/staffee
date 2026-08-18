<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_person', 150)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number', 20)->nullable();
            $table->string('country', 60)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
