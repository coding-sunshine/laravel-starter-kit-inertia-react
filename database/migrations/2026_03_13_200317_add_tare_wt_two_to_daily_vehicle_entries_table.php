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
            $table->decimal('tare_wt_two', 10, 2)->nullable()->after('tare_wt');
        });

        DB::statement('UPDATE daily_vehicle_entries SET tare_wt_two = tare_wt WHERE tare_wt_two IS NULL AND tare_wt IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table) {
            $table->dropColumn('tare_wt_two');
        });
    }
};
