<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->foreignId('issued_by')->constrained('users');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('INR');
            $table->decimal('subtotal', 18, 6);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);
            $table->decimal('total_amount', 18, 6);
            $table->decimal('amount_paid', 18, 6)->default(0);
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
