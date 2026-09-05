<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FreightRateMasterSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            // Class 145A (Train Load)
            ['COAL', 'Coal', '145A', 1, 100, 216.00],
            ['COAL', 'Coal', '145A', 101, 125, 389.60],
            ['COAL', 'Coal', '145A', 126, 150, 448.90],
            ['COAL', 'Coal', '145A', 151, 175, 488.70],
            ['COAL', 'Coal', '145A', 176, 200, 532.20],
            ['COAL', 'Coal', '145A', 201, 275, 672.50],
            ['COAL', 'Coal', '145A', 276, 350, 797.20],
            ['COAL', 'Coal', '145A', 351, 425, 926.00],
            ['COAL', 'Coal', '145A', 426, 500, 1054.70],
            ['COAL', 'Coal', '145A', 501, 600, 1228.00],
            ['COAL', 'Coal', '145A', 601, 700, 1398.70],
            ['COAL', 'Coal', '145A', 971, 1000, 1891.80],

            // Class 145B (Wagon Load)
            ['COAL', 'Coal', '145B', 1, 100, 226.80],
            ['COAL', 'Coal', '145B', 101, 125, 409.10],
            ['COAL', 'Coal', '145B', 126, 150, 471.30],
            ['COAL', 'Coal', '145B', 151, 175, 513.10],
            ['COAL', 'Coal', '145B', 176, 200, 559.00],
            ['COAL', 'Coal', '145B', 201, 275, 706.10],
            ['COAL', 'Coal', '145B', 276, 350, 837.10],
            ['COAL', 'Coal', '145B', 351, 425, 972.30],
            ['COAL', 'Coal', '145B', 426, 500, 1107.40],
            ['COAL', 'Coal', '145B', 501, 600, 1289.40],
            ['COAL', 'Coal', '145B', 601, 700, 1468.70],
            ['COAL', 'Coal', '145B', 971, 1000, 1986.30],
        ];

        $insertData = [];

        foreach ($data as $row) {
            $insertData[] = [
                'commodity_code' => $row[0],
                'commodity_name' => $row[1],
                'class_code' => $row[2],
                'distance_from_km' => $row[3],
                'distance_to_km' => $row[4],
                'rate_per_mt' => $row[5],
                'risk_rate' => null,
                'gst_percent' => 5.00,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('freight_rate_master')->insert($insertData);
    }
}
