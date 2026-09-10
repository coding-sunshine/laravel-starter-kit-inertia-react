<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One DPR row per siding_vehicle_dispatches row (left-join shape), not deduped by (siding_id, e_challan_no).
     */
    public function up(): void
    {
        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->dropUnique(['siding_id', 'e_challan_no']);
        });

        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->foreignId('vehicle_dispatch_id')
                ->nullable()
                ->constrained('siding_vehicle_dispatches')
                ->cascadeOnDelete();
            $table->unique('vehicle_dispatch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->dropUnique(['vehicle_dispatch_id']);
            $table->dropConstrainedForeignId('vehicle_dispatch_id');
        });

        Schema::table('dispatch_reports', function (Blueprint $table): void {
            $table->unique(['siding_id', 'e_challan_no']);
        });
    }
};
