<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termination_id')->unique()->constrained('termination_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_complete')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('exit_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('exit_checklists')->cascadeOnDelete();
            $table->enum('category', [
                'assets',             // return laptop, badge, etc.
                'access',             // revoke email, VPN, GitHub, etc.
                'knowledge_transfer', // handover docs, code walkthroughs
                'documentation',      // NOC, experience letter, etc.
                'finance',            // pending reimbursements, advances
                'hr',                 // exit interview, PF/gratuity forms
            ]);
            $table->string('item');                 // "Return company laptop"
            $table->text('description')->nullable();
            $table->foreignId('responsible_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();            // who must verify completion
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('checklist_id');
            $table->index(['checklist_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_checklist_items');
        Schema::dropIfExists('exit_checklists');
    }
};
