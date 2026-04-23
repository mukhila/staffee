<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Link to structured leave types (nullable for backward-compat with legacy rows)
            $table->foreignId('leave_type_id')->nullable()->after('user_id')
                ->constrained('leave_types')->nullOnDelete();

            // Half-day support
            $table->boolean('half_day')->default(false)->after('days');
            $table->enum('half_day_period', ['morning', 'afternoon'])->nullable()->after('half_day');

            // Approval tracking
            $table->foreignId('manager_approved_by')->nullable()->after('reviewed_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->foreignId('hr_approved_by')->nullable()->after('manager_approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
            $table->boolean('auto_approved')->default(false)->after('hr_approved_at');

            // Cancellation
            $table->timestamp('cancelled_at')->nullable()->after('auto_approved');
            $table->text('cancelled_reason')->nullable()->after('cancelled_at');

            $table->index(['user_id', 'status', 'from_date']);
            $table->index(['leave_type_id', 'from_date']);
        });

        // Expand status enum to include new workflow states
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status
            ENUM('pending','manager_approved','approved','rejected','cancelled','auto_approved')
            NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status
            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropForeign(['manager_approved_by']);
            $table->dropForeign(['hr_approved_by']);
            $table->dropColumn([
                'leave_type_id', 'half_day', 'half_day_period',
                'manager_approved_by', 'manager_approved_at',
                'hr_approved_by', 'hr_approved_at',
                'auto_approved', 'cancelled_at', 'cancelled_reason',
            ]);
        });
    }
};
