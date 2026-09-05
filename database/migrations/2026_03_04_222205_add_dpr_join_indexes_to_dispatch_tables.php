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
     * Indexes for efficient DPR join:
     * siding_vehicle_dispatches JOIN daily_vehicle_entries
     * ON daily_vehicle_entries.e_challan_no = siding_vehicle_dispatches.pass_no
     * AND daily_vehicle_entries.siding_id = siding_vehicle_dispatches.siding_id
     */
    public function up(): void
    {
        Schema::table('siding_vehicle_dispatches', function (Blueprint $table) {
            $table->index('pass_no');
            $table->index(['siding_id', 'pass_no']);
        });

        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->index('e_challan_no');
            $table->index(['siding_id', 'e_challan_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siding_vehicle_dispatches', function (Blueprint $table) {
            $table->dropIndex(['pass_no']);
            $table->dropIndex(['siding_id', 'pass_no']);
        });

        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->dropIndex(['e_challan_no']);
            $table->dropIndex(['siding_id', 'e_challan_no']);
        });
    }
};
