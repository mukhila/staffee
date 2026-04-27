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
        Schema::table('bugs', function (Blueprint $table) {
            $table->text('resolution_notes')->nullable()->after('test_case_id');
            $table->timestamp('resolved_at')->nullable()->after('resolution_notes');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('closed_at');
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolution_notes', 'resolved_at', 'closed_at', 'resolved_by']);
        });
    }
};
