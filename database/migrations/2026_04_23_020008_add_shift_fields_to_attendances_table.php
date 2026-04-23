<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            $table->unsignedSmallInteger('worked_minutes')->nullable()->after('check_out');
            $table->smallInteger('overtime_minutes')->nullable()->after('worked_minutes');
            $table->boolean('is_shift_day')->default(false)->after('overtime_minutes');
            $table->timestamp('validated_at')->nullable()->after('is_shift_day');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['shift_id', 'worked_minutes', 'overtime_minutes', 'is_shift_day', 'validated_at']);
        });
    }
};
