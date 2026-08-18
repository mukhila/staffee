<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 50)->unique();
            $table->string('name', 150);
            $table->enum('category', ['laptop','desktop','phone','tablet','monitor','peripheral','vehicle','furniture','software_license','other']);
            $table->string('brand', 100)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->string('serial_number', 150)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 6)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['available','assigned','in_repair','retired','lost'])->default('available');
            $table->string('location', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
