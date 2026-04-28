<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_screenshots', function (Blueprint $table) {
            $table->string('review_status')->default('pending')->after('flag_reason');
            // pending | accepted | escalated
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_screenshots', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['review_status', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
