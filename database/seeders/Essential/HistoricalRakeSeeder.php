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
        // $file = storage_path('app/excel/rake-history.xlsx');
        $file = database_path('excel/rake-history.xlsx');

        Excel::import(
            new HistoricalRakeImport(1), // siding_id
            $file
        );
    }
}
