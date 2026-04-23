<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Unique company-issued identifier (EMP-001)
            $table->string('employee_id')->nullable()->unique()->after('id');

            // Job title — distinct from the system role (admin/pm/staff)
            $table->string('designation')->nullable()->after('role');

            // HR lifecycle status
            $table->enum('employment_status', [
                'active', 'probation', 'notice_period', 'suspended', 'terminated', 'resigned',
            ])->default('active')->after('is_active');

            $table->index('employment_status');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['employment_status']);
            $table->dropColumn(['employee_id', 'designation', 'employment_status']);
        });
    }
};
