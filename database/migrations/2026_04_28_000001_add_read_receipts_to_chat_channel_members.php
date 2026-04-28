<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_channel_members', function (Blueprint $table) {
            $table->unsignedBigInteger('last_read_message_id')->nullable()->after('user_id');
            $table->timestamp('last_read_at')->nullable()->after('last_read_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_channel_members', function (Blueprint $table) {
            $table->dropColumn(['last_read_message_id', 'last_read_at']);
        });
    }
};
