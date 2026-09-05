<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->text('remarks')->nullable()->after('transport_name');
            $table->decimal('net_wt', 10, 2)->nullable()->after('tare_wt_two');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("UPDATE daily_vehicle_entries SET net_wt = ROUND((gross_wt::numeric - tare_wt::numeric), 2) WHERE status = 'completed' AND entry_type = 'road_dispatch' AND gross_wt IS NOT NULL AND tare_wt IS NOT NULL AND net_wt IS NULL AND (gross_wt::numeric - tare_wt::numeric) > 0");
        } else {
            DB::statement("UPDATE daily_vehicle_entries SET net_wt = ROUND(gross_wt - tare_wt, 2) WHERE status = 'completed' AND entry_type = 'road_dispatch' AND gross_wt IS NOT NULL AND tare_wt IS NOT NULL AND net_wt IS NULL AND (gross_wt - tare_wt) > 0");
        }
    }

    public function down(): void
    {
        Schema::table('daily_vehicle_entries', function (Blueprint $table): void {
            $table->dropColumn(['remarks', 'net_wt']);
        });
    }
};
