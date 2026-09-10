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
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->foreignId('daily_vehicle_entry_id')
                ->nullable()
                ->after('rake_id')
                ->constrained('daily_vehicle_entries')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->dropForeign(['daily_vehicle_entry_id']);
        });
    }
};
