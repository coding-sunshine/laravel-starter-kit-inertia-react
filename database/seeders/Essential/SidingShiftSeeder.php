<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\Siding;
use App\Models\SidingShift;
use Illuminate\Database\Seeder;

final class SidingShiftSeeder extends Seeder
{
    /**
     * Seeder dependencies.
     *
     * @var array<string>
     */
    public array $dependencies = [
        'SidingSeeder',
    ];

    public function run(): void
    {
        $sidings = Siding::query()->get();

        foreach ($sidings as $siding) {
            $baseName = str($siding->name)->before(' ')->title()->toString();

            // Client timings: 1st 12:01 AM–08:00 AM, 2nd 08:01 AM–04:00 PM, 3rd 04:01 PM–12:00 AM
            $shifts = [
                [
                    'shift_name' => "{$baseName} 1st Shift",
                    'start_time' => '00:01:00',
                    'end_time' => '08:00:00',
                    'sort_order' => 1,
                ],
                [
                    'shift_name' => "{$baseName} 2nd Shift",
                    'start_time' => '08:01:00',
                    'end_time' => '16:00:00',
                    'sort_order' => 2,
                ],
                [
                    'shift_name' => "{$baseName} 3rd Shift",
                    'start_time' => '16:01:00',
                    'end_time' => '00:00:00', // midnight (overnight)
                    'sort_order' => 3,
                ],
            ];

            foreach ($shifts as $data) {
                SidingShift::query()->updateOrCreate(
                    [
                        'siding_id' => $siding->id,
                        'sort_order' => $data['sort_order'],
                    ],
                    [
                        'shift_name' => $data['shift_name'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'is_active' => true,
                        'description' => null,
                    ],
                );
            }
        }
    }
}
