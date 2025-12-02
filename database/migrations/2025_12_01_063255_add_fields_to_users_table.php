<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff'); // admin, staff, pm
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('reporting_to')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->string('avatar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['reporting_to']);
            $table->dropColumn(['role', 'phone', 'address', 'department_id', 'reporting_to', 'is_active', 'avatar']);
        });
    }
};
