<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_vehicle_entry_rollups', function (Blueprint $table): void {
            $table->id();

            /** Sheet calendar day — matches {@see App\Models\DailyVehicleEntry::$entry_date} grain used on road dispatch screens. */
            $table->date('rollup_day');

            $table->foreignId('siding_id')->constrained()->cascadeOnDelete();

            /** Shift ordinal ({@code 1}/{@code 2}/{@code 3}) from {@see App\Models\DailyVehicleEntry::$shift}. */
            $table->unsignedTinyInteger('shift');

            $table->unsignedInteger('entries_count');
            $table->unsignedInteger('completed_entries_count');
            $table->unsignedInteger('pending_entries_count');

            $table->decimal('completed_net_wt_mt', 14, 2)->default('0');
            $table->decimal('pending_gross_wt_mt', 14, 2)->default('0');

            $table->timestamps();

            $table->unique(
                ['rollup_day', 'siding_id', 'shift'],
                'daily_dve_rollups_day_siding_shift_uq'
            );

            $table->index(['siding_id', 'rollup_day'], 'daily_dve_rollups_siding_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_vehicle_entry_rollups');
    }
};
