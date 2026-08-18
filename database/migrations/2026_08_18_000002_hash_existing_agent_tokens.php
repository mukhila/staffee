<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing plaintext tokens cannot be rehashed — force reissue.
        DB::table('users')->whereNotNull('agent_token')->update(['agent_token' => null]);
    }

    public function down(): void
    {
        // Cannot restore plaintext tokens.
    }
};
