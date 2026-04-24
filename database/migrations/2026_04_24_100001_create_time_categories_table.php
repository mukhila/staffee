<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('is_billable')->default(true);
            $table->string('color', 7)->default('#6366f1');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed defaults
        \DB::table('time_categories')->insert([
            ['name' => 'Development',    'is_billable' => true,  'color' => '#3b82f6', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Testing',        'is_billable' => true,  'color' => '#10b981', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Code Review',    'is_billable' => true,  'color' => '#f59e0b', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Documentation',  'is_billable' => true,  'color' => '#8b5cf6', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Support',        'is_billable' => true,  'color' => '#06b6d4', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meetings',       'is_billable' => false, 'color' => '#6b7280', 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admin',          'is_billable' => false, 'color' => '#ef4444', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('time_categories');
    }
};
