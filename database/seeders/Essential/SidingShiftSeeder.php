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

            $shifts = [
                [
                    'shift_name' => "{$baseName} Shift 1",
                    'start_time' => '06:00:00',
                    'end_time' => '14:00:00',
                    'sort_order' => 1,
                ],
                [
                    'shift_name' => "{$baseName} Shift 2",
                    'start_time' => '14:00:00',
                    'end_time' => '22:00:00',
                    'sort_order' => 2,
                ],
                [
                    'shift_name' => "{$baseName} Shift 3",
                    'start_time' => '22:00:00',
                    'end_time' => '06:00:00',
                    'sort_order' => 3,
                ],
            ];

            foreach ($shifts as $data) {
                SidingShift::query()->firstOrCreate(
                    [
                        'siding_id' => $siding->id,
                        'shift_name' => $data['shift_name'],
                    ],
                    [
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'is_active' => true,
                        'sort_order' => $data['sort_order'],
                        'description' => null,
                    ],
                );
            }
        }
    }
}
