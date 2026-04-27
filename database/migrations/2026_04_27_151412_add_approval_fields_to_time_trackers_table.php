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
        Schema::table('time_trackers', function (Blueprint $table) {
            // null = auto-approved (same-day / recent); explicit values for old entries needing review
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable()->after('notes');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_trackers', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_notes']);
        });
    }
};
