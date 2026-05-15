<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stock_ledgers.daily_vehicle_entry_id had no index. The road-trip summary
 * resolves "stock added" by EXISTS against this column for every
 * daily_vehicle_entries row, which forced a full sequential scan per row and
 * pegged Postgres. Index it so the lookup is a btree probe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->index('daily_vehicle_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table): void {
            $table->dropIndex(['daily_vehicle_entry_id']);
        });
    }
};
