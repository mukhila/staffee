<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planned'); // planned|active|completed|cancelled
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete()->after('project_id');
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->nullOnDelete()->after('sprint_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Sprint::class);
            $table->dropForeignIdFor(\App\Models\Milestone::class);
            $table->dropColumn(['sprint_id', 'milestone_id']);
        });
        Schema::dropIfExists('sprints');
    }
};
