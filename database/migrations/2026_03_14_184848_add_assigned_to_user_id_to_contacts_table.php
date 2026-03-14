<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreignId('assigned_to_user_id')->nullable()->after('lead_score')->constrained('users')->nullOnDelete();
            $table->index('assigned_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropForeignIdFor(App\Models\User::class, 'assigned_to_user_id');
            $table->dropColumn('assigned_to_user_id');
        });
    }
};
