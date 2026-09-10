<?php

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
        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->string('trip_id_no')->nullable();
            $table->string('transport_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->dropColumn(['trip_id_no', 'transport_name']);
        });
    }
};
