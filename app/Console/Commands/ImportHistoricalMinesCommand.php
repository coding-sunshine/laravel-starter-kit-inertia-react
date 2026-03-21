<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportHistoricalMinesDataFromExcelAction;
use Illuminate\Console\Command;
use Throwable;

final class ImportHistoricalMinesCommand extends Command
{
    protected $signature = 'historical-mines:import {--file= : Path to xlsx (default: database/excel/historical_trip_data.xlsx)}';

    protected $description = 'Import historical mines data from the PRD Excel file';

    public function handle(ImportHistoricalMinesDataFromExcelAction $action): int
    {
        $file = $this->option('file');
        $defaultFile = database_path('excel/historical_trip_data.xlsx');
        $path = is_string($file) && mb_trim($file) !== '' ? (string) $file : $defaultFile;

        if (str_starts_with($path, '/')) {
            $resolvedPath = $path;
        } else {
            $resolvedPath = base_path($path);
        }

        if (! is_file($resolvedPath)) {
            $this->error('File not found: '.$resolvedPath);

            return self::FAILURE;
        }

        try {
            $stats = $action->handle($resolvedPath);
        } catch (Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Historical mines import completed.');
        $this->table(
            ['rows_processed', 'created', 'updated', 'skipped', 'errors'],
            [[
                $stats['rows_processed'] ?? 0,
                $stats['created'] ?? 0,
                $stats['updated'] ?? 0,
                $stats['skipped'] ?? 0,
                count($stats['errors'] ?? []),
            ]],
        );

        if (! empty($stats['errors'])) {
            $this->line('Errors:');
            foreach ($stats['errors'] as $error) {
                $this->line('- '.$error);
            }
        }

        return self::SUCCESS;
    }
}
