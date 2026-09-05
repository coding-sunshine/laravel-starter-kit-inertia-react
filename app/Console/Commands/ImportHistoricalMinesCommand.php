<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportHistoricalMinesDataFromExcelAction;
use App\Models\HistoricalMine;
use App\Models\Siding;
use Illuminate\Console\Command;
use Throwable;

final class ImportHistoricalMinesCommand extends Command
{
    private const string DEFAULT_KURWA_FILENAME = 'KURWA_DATA_2024_ TO_2026.xlsx';

    protected $signature = 'historical-mines:import
                            {--file= : Path to one xlsx; omit to import default Pakur, Kurwa, and Dumka workbooks}
                            {--siding-code= : Sidings.code (PKUR, KURWA, DUMK) when using --file; inferred from the filename if omitted}';

    protected $description = 'Import historical mines trip Excel data into historical_mines (per-row), with siding from file or option';

    public function handle(ImportHistoricalMinesDataFromExcelAction $action): int
    {
        $fileOption = $this->option('file');
        $trimmedFile = is_string($fileOption) ? mb_trim($fileOption) : '';

        if ($trimmedFile !== '') {
            $resolvedPath = $this->resolveSpreadsheetPath($trimmedFile);
            if (! is_file($resolvedPath)) {
                $this->error('File not found: '.$resolvedPath);

                return self::FAILURE;
            }

            $sidingCode = $this->resolveSidingCodeForSingleImport($trimmedFile);
            $sidingId = $this->requireSidingId($sidingCode);
            if ($sidingId === null) {
                return self::FAILURE;
            }

            $jobs = [['path' => $resolvedPath, 'label' => basename($resolvedPath), 'siding_id' => $sidingId, 'code' => $sidingCode]];
        } else {
            $jobs = [
                [
                    'path' => database_path('excel/historical_trip_data.xlsx'),
                    'label' => 'historical_trip_data.xlsx (Pakur)',
                    'code' => 'PKUR',
                ],
                [
                    'path' => database_path('excel/'.self::DEFAULT_KURWA_FILENAME),
                    'label' => self::DEFAULT_KURWA_FILENAME.' (Kurwa)',
                    'code' => 'KURWA',
                ],
                [
                    'path' => database_path('excel/DUMKA_DATA_2024_TO_2026.xlsx'),
                    'label' => 'DUMKA_DATA_2024_TO_2026.xlsx (Dumka)',
                    'code' => 'DUMK',
                ],
            ];

            foreach ($jobs as &$job) {
                if (! is_file($job['path'])) {
                    $this->error('File not found: '.$job['path']);

                    return self::FAILURE;
                }

                $id = $this->requireSidingId($job['code']);
                if ($id === null) {
                    return self::FAILURE;
                }

                $job['siding_id'] = $id;
            }

            unset($job);
        }

        $deleted = HistoricalMine::query()->delete();
        if ($deleted > 0) {
            $this->info(sprintf('Removed %d existing historical mine row(s) before import.', $deleted));
        }

        $totals = [
            'rows_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($jobs as $job) {
            try {
                $stats = $action->handle($job['path'], $job['siding_id']);
            } catch (Throwable $e) {
                $this->error('Import failed ['.$job['label'].']: '.$e->getMessage());

                return self::FAILURE;
            }

            $totals['rows_processed'] += $stats['rows_processed'] ?? 0;
            $totals['created'] += $stats['created'] ?? 0;
            $totals['updated'] += $stats['updated'] ?? 0;
            $totals['skipped'] += $stats['skipped'] ?? 0;
            foreach ($stats['errors'] ?? [] as $error) {
                $totals['errors'][] = '['.$job['label'].'] '.$error;
            }

            $this->info(sprintf(
                'Imported %s (siding %s): %d row(s) processed, %d created.',
                $job['label'],
                $job['code'],
                $stats['rows_processed'] ?? 0,
                $stats['created'] ?? 0,
            ));
        }

        $this->info('Historical mines import completed (all files).');
        $this->table(
            ['rows_processed', 'created', 'updated', 'skipped', 'errors'],
            [[
                $totals['rows_processed'],
                $totals['created'],
                $totals['updated'],
                $totals['skipped'],
                count($totals['errors']),
            ]],
        );

        if ($totals['errors'] !== []) {
            $this->line('Errors:');
            foreach ($totals['errors'] as $error) {
                $this->line('- '.$error);
            }
        }

        return self::SUCCESS;
    }

    private function resolveSpreadsheetPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function resolveSidingCodeForSingleImport(string $filePathOrRelative): string
    {
        $explicit = $this->option('siding-code');
        if (is_string($explicit) && mb_trim($explicit) !== '') {
            return mb_strtoupper(mb_trim($explicit));
        }

        $base = mb_strtoupper(basename($filePathOrRelative));

        if (str_contains($base, 'KURWA')) {
            return 'KURWA';
        }

        if (str_contains($base, 'DUMKA')) {
            return 'DUMK';
        }

        return 'PKUR';
    }

    private function requireSidingId(string $code): ?int
    {
        $id = Siding::query()->where('code', $code)->value('id');
        if ($id === null) {
            $this->error(sprintf('No siding found for code "%s". Seed sidings (e.g. SidingSeeder) or fix --siding-code.', $code));

            return null;
        }

        return (int) $id;
    }
}
