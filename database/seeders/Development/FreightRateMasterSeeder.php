<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\FreightRateMaster;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * FreightRateMaster seeder. Seeds demo freight rate rows for development.
 */
final class FreightRateMasterSeeder extends Seeder
{
    public function run(): void
    {
        if (FreightRateMaster::query()->exists()) {
            return;
        }

        $today = Carbon::today();
        FreightRateMaster::query()->create([
            'commodity_code' => 'COAL',
            'commodity_name' => 'Coal',
            'class_code' => '120',
            'risk_rate' => 1.0,
            'distance_from_km' => 0,
            'distance_to_km' => 500,
            'rate_per_mt' => 1250.00,
            'gst_percent' => 5,
            'effective_from' => $today->copy()->subMonths(6),
            'effective_to' => $today->copy()->addYear(),
            'is_active' => true,
        ]);
    }
}
