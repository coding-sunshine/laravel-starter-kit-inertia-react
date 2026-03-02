<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->string('agent', 64)->nullable()->after('user_id');
            $table->index('agent');
        });
    }

    public function down(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->dropIndex(['agent']);
            $table->dropColumn('agent');
        });
    }
};
