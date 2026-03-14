<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->string('entry_type', 50)->default('road_dispatch')->after('shift');
        });

        DB::table('daily_vehicle_entries')->whereNull('entry_type')->update(['entry_type' => 'road_dispatch']);

        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->index(['entry_type', 'entry_date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->dropIndex(['entry_type', 'entry_date', 'shift']);
            $table->dropColumn('entry_type');
        });
    }
};
