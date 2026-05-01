<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\CommodityUtilisationThreshold;
use Illuminate\Database\Seeder;

final class CommodityUtilisationThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['commodity_grade' => 'G1', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G2', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G3', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G4', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G5', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'UNGRADED', 'utilisation_threshold' => 0.95],
        ];

        foreach ($defaults as $row) {
            CommodityUtilisationThreshold::query()->updateOrCreate(
                ['commodity_grade' => $row['commodity_grade'], 'effective_from' => '2025-01-01 00:00:00'],
                [
                    'utilisation_threshold' => $row['utilisation_threshold'],
                    'effective_to' => null,
                    'source' => 'Stage-1 default — adjust per calibration',
                ],
            );
        }
    }
}
