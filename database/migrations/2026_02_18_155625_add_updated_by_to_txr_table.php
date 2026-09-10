<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('txr', 'updated_by')) {
            return;
        }

        Schema::table('txr', function (Blueprint $table): void {
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('txr', 'updated_by')) {
            return;
        }

        Schema::table('txr', function (Blueprint $table): void {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};
