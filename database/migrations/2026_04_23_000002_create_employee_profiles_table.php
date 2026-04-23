<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Personal details
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();          // passport / NIC / Aadhaar
            $table->string('national_id_type')->nullable();     // 'passport', 'nic', 'aadhaar', etc.

            // Structured address (permanent/official — different from the free-text users.address)
            $table->string('perm_address_line1')->nullable();
            $table->string('perm_address_line2')->nullable();
            $table->string('perm_city')->nullable();
            $table->string('perm_state')->nullable();
            $table->string('perm_postal_code')->nullable();
            $table->string('perm_country')->nullable();

            // Employment details
            $table->date('joining_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->enum('contract_type', [
                'permanent', 'fixed_term', 'internship', 'part_time', 'consultant',
            ])->default('permanent');
            $table->date('contract_end_date')->nullable();      // null for permanent
            $table->integer('notice_period_days')->default(30);
            $table->string('work_location')->nullable();        // office | remote | hybrid | city name

            // Compensation baseline (actual history tracked in salary_revisions)
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('USD');

            // Social links
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->text('bio')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
