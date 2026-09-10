<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_siding_vehicle_dispatch_rollups', function (Blueprint $table): void {
            $table->id();

            $table->date('issued_on_date');
            $table->foreignId('siding_id')->constrained()->cascadeOnDelete();

            /** Derived from {@see App\Models\SidingVehicleDispatch::$shift}: {@code 1st}/{@code 2nd}/{@code 3rd} → {@code 1}/{@code 2}/{@code 3}; unknown → {@code 0}. */
            $table->unsignedTinyInteger('shift_number')->default(0);

            $table->unsignedInteger('dispatches_count');
            $table->decimal('qty_mineral_mt', 14, 2)->default('0');

            $table->timestamps();

            $table->unique(
                ['issued_on_date', 'siding_id', 'shift_number'],
                'daily_svd_rollups_day_siding_shift_uq'
            );

            $table->index(['siding_id', 'issued_on_date'], 'daily_svd_rollups_siding_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_siding_vehicle_dispatch_rollups');
    }
};
