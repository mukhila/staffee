<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->unsignedInteger('version_no')->default(1)->after('monthly_base_salary');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->unsignedTinyInteger('for_month')->nullable()->after('payroll_calendar_id');
            $table->unsignedSmallInteger('for_year')->nullable()->after('for_month');
            $table->unique(['for_month', 'for_year', 'run_type'], 'payroll_runs_month_year_type_unique');
        });

        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->string('slip_number', 50)->nullable()->after('salary_structure_id');
            $table->unique('slip_number');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropUnique(['slip_number']);
            $table->dropColumn('slip_number');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique('payroll_runs_month_year_type_unique');
            $table->dropColumn(['for_month', 'for_year']);
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->dropColumn('version_no');
        });
    }
};
