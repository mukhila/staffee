<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report', 50);
            $table->string('frequency', 10);   // weekly (Mondays, last week) | monthly (1st, last month)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'report', 'frequency', 'department_id'], 'report_subscriptions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_subscriptions');
    }
};
