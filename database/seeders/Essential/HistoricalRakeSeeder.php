<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Services\HistoricalRakeImportService;
use Illuminate\Database\Seeder;

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

        app(HistoricalRakeImportService::class)->handle($file, sidingId: 1);
    }
}
