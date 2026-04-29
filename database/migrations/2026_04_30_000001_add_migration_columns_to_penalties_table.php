<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penalties', function (Blueprint $table): void {
            $table->dateTime('migrated_at')->nullable()->after('updated_at');
            $table->string('migration_note')->nullable()->after('migrated_at');
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table): void {
            $table->dropColumn(['migrated_at', 'migration_note']);
        });
    }
};
