<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_trackers', function (Blueprint $table) {
            // Link to time category (null = uncategorised)
            $table->foreignId('category_id')->nullable()->after('description')
                  ->constrained('time_categories')->nullOnDelete();

            // Explicit project link (in addition to polymorphic trackable)
            $table->foreignId('project_id')->nullable()->after('category_id')
                  ->constrained()->nullOnDelete();

            // Billing
            $table->boolean('is_billable')->default(true)->after('project_id');

            // Stored computed duration — avoids TIMESTAMPDIFF in every report query
            $table->decimal('hours_decimal', 8, 4)->nullable()->after('is_billable');

            // Rate captured at stop time so historical rate changes don't mutate past revenue
            $table->decimal('rate_snapshot', 10, 2)->nullable()->after('hours_decimal');

            // Optional extra notes (description already used for what-was-done)
            $table->string('notes', 500)->nullable()->after('rate_snapshot');

            $table->index(['user_id', 'start_time']);
            $table->index(['project_id', 'is_billable', 'start_time']);
            $table->index(['category_id', 'is_billable']);
        });
    }

    public function down(): void
    {
        Schema::table('time_trackers', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['category_id', 'project_id', 'is_billable', 'hours_decimal', 'rate_snapshot', 'notes']);
        });
    }
};
