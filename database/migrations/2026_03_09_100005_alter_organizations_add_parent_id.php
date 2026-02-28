<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            if (! Schema::hasColumn('organizations', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('owner_id')->constrained('organizations')->nullOnDelete();
                $table->index('parent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            if (Schema::hasColumn('organizations', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }
        });
    }
};
