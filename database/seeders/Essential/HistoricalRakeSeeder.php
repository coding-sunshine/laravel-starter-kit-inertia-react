<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Imports\HistoricalRakeImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

final class HistoricalRakeSeeder extends Seeder
{
    public array $dependencies = [
        'SidingSeeder',
        'PenaltyTypesSeeder',
    ];

    public function run(): void
    {
        $file = database_path('excel/rake-history.xlsx');

        if (! is_file($file)) {
            $this->command?->info('HistoricalRakeSeeder skipped: database/excel/rake-history.xlsx not found.');

            return;
        }

        Excel::import(
            new HistoricalRakeImport(1), // siding_id
            $file
        );
    }
}
