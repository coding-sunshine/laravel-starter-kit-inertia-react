<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\SectionTimer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionTimersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectionTimers = [
            [
                'section_name' => 'loading',
                'free_minutes' => 180,
                'warning_minutes' => 150,
                'penalty_applicable' => true,
            ],
            [
                'section_name' => 'guard',
                'free_minutes' => 60,
                'warning_minutes' => 45,
                'penalty_applicable' => true,
            ],
            [
                'section_name' => 'weighment',
                'free_minutes' => 60,
                'warning_minutes' => 45,
                'penalty_applicable' => true,
            ],
        ];

        foreach ($sectionTimers as $timer) {
            SectionTimer::updateOrCreate(['section_name' => $timer['section_name']], $timer);
        }
    }
}
