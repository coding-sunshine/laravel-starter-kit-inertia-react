<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductionEntry;
use App\Support\Imports\ProductionExcelImporter;
use Illuminate\Console\Command;
use Throwable;

final class ImportCoalProductionFromExcel extends Command
{
    protected $signature = 'production:import-coal
                            {--file= : Path to xlsx (default: database/excel/producation coal.xlsx)}
                            {--sheet= : Sheet name (default: first sheet)}
                            {--dry-run : Parse only, do not write to DB}
                            {--log-skipped : Print rows skipped during import}';

    protected $description = 'Import month-wise coal production from Excel into production_entries';

    public function handle(ProductionExcelImporter $importer): int
    {
        $resolvedPath = $this->resolvePath(
            $this->option('file'),
            database_path('excel/producation coal.xlsx')
        );

        if (! is_file($resolvedPath)) {
            $this->error('File not found: '.$resolvedPath);

            return self::FAILURE;
        }

        $sheet = $this->option('sheet');
        $sheetName = is_string($sheet) && mb_trim($sheet) !== '' ? (string) $sheet : null;
        $dryRun = (bool) $this->option('dry-run');
        $logSkipped = (bool) $this->option('log-skipped');

        try {
            $stats = $importer->import($resolvedPath, ProductionEntry::TYPE_COAL, $sheetName, $dryRun, $logSkipped);
        } catch (Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Coal production import completed.');
        $this->table(
            ['rows_processed', 'created', 'skipped', 'errors'],
            [[
                $stats['rows_processed'] ?? 0,
                $stats['created'] ?? 0,
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

        if ($logSkipped && ! empty($stats['skipped_rows'])) {
            $this->newLine();
            $this->info('Skipped rows (Month/total coal):');
            $this->table(
                ['sheet', 'row', 'month', 'qty', 'reason'],
                array_map(
                    static fn (array $r): array => [$r['sheet'], $r['row'], (string) $r['month'], (string) $r['qty'], (string) $r['reason']],
                    $stats['skipped_rows'],
                ),
            );
        }

        return self::SUCCESS;
    }

    private function resolvePath(mixed $fileOption, string $defaultFile): string
    {
        $path = is_string($fileOption) && mb_trim($fileOption) !== '' ? (string) $fileOption : $defaultFile;

        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}
