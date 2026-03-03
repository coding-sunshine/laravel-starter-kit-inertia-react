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
            $table->foreignId('organization_id')->nullable()->after('user_id');
            $table->index(['organization_id', 'user_id', 'updated_at'], 'agent_conv_org_user_updated');
        });
    }

    public function down(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table): void {
            $table->dropIndex('agent_conv_org_user_updated');
            $table->dropColumn('organization_id');
        });
    }
};
