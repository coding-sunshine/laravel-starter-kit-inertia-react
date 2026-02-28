<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('agent_conversation_messages')
            ->whereNull('content')
            ->update(['content' => '']);
    }

    public function down(): void
    {
        // Cannot restore nulls; no-op.
    }
};
