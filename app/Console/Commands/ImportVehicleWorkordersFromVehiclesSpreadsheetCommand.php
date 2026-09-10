<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportVehicleWorkordersFromVehiclesSpreadsheetAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

final class ImportVehicleWorkordersFromVehiclesSpreadsheetCommand extends Command
{
    protected $signature = 'vehicle-workorders:import-vehicles-spreadsheet
                            {--file=database/excel/vehicles.xlsx : Path to the XLSX file}
                            {--dry-run : Parse and show values that would be stored; no database writes}';

    protected $description = 'Import vehicle workorders from vehicles spreadsheet (normalized REGD. NO / vehicle_no; first row wins within the file).';

    public function handle(ImportVehicleWorkordersFromVehiclesSpreadsheetAction $action): int
    {
        $file = (string) $this->option('file');
        $path = str_starts_with($file, '/') ? $file : base_path($file);

        if (! File::exists($path)) {
            $this->error(sprintf('File not found: %s', $path));

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            try {
                $preview = $action->dryRun($path);
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->warn('Dry run — no database inserts or updates were performed.');
            $this->newLine();
            $this->comment('Create/update totals are per unique REGD. NO after duplicate rows are skipped (first row wins — same rules as import).');
            $this->newLine();

            if ($preview['skipped'] !== []) {
                $this->warn('Skipped spreadsheet rows:');
                $this->table(
                    ['Excel row', 'Reason'],
                    array_map(
                        fn (array $row): array => [(string) $row['excel_row'], $row['reason']],
                        $preview['skipped'],
                    ),
                );
                $this->newLine();
            }

            foreach ($preview['records'] as $i => $row) {
                $n = $i + 1;
                $this->line(sprintf(
                    '<fg=cyan>#%d</> Excel row <fg=yellow>%d</> — <fg=white>%s</> — <comment>%s</>',
                    $n,
                    $row['excel_row'],
                    $row['vehicle_no'],
                    mb_strtoupper($row['outcome']),
                ));

                ksort($row['attributes']);

                try {
                    $json = json_encode(
                        $row['attributes'],
                        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
                    );
                } catch (JsonException $e) {
                    $this->error('Could not encode record as JSON: '.$e->getMessage());

                    return self::FAILURE;
                }

                $this->line($json);
                $this->newLine();
            }

            if ($preview['records'] === []) {
                $this->comment('No rows with a valid REGD. NO and resolvable siding (WO NO) to import.');
            }

            $wouldCreate = $preview['stats']['would_create'];
            $wouldUpdate = $preview['stats']['would_update'];
            $wouldAffected = $wouldCreate + $wouldUpdate;

            $this->newLine();
            $this->info('Totals (dry run)');
            $this->line(sprintf('Total would create: %d', $wouldCreate));
            $this->line(sprintf('Total would update: %d', $wouldUpdate));
            $this->line(sprintf('Total rows that would be affected: %d', $wouldAffected));
            $this->line(sprintf('Total with tare null, zero, or negative (merged rows): %d', $preview['stats']['tare_weight_null_or_non_positive']));
            $this->line(sprintf('Spreadsheet rows skipped: %d', $preview['stats']['skipped']));

            if ($preview['tare_weight_issue_rows'] !== []) {
                $this->newLine();
                $this->warn('Merged rows with tare null, zero, or negative (from spreadsheet):');
                $this->comment('Tare (database) is the value before this import (no row yet = —).');
                $this->table(
                    [
                        'Vehicle workorder ID',
                        'Vehicle no.',
                        'Tare (XLSX)',
                        'Tare (database)',
                        'Outcome',
                    ],
                    array_map(
                        fn (array $row): array => [
                            $row['vehicle_workorder_id'] !== null
                                ? (string) $row['vehicle_workorder_id']
                                : '— (would create)',
                            $row['vehicle_no'],
                            self::formatTareForCli($row['tare_weight_xlsx']),
                            self::formatTareForCli($row['tare_weight_database']),
                            mb_strtoupper($row['outcome']),
                        ],
                        $preview['tare_weight_issue_rows'],
                    ),
                );
            }

            return self::SUCCESS;
        }

        try {
            $stats = $action->handle($path);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $created = $stats['created'];
        $updated = $stats['updated'];
        $affected = $created + $updated;

        $this->newLine();
        $this->info('Totals');
        $this->line(sprintf('Total created: %d', $created));
        $this->line(sprintf('Total updated: %d', $updated));
        $this->line(sprintf('Total rows affected: %d', $affected));
        $this->line(sprintf('Total with tare null, zero, or negative (merged rows): %d', $stats['tare_weight_null_or_non_positive']));
        $this->line(sprintf('Spreadsheet rows skipped: %d', $stats['skipped']));

        if ($stats['tare_weight_issue_rows'] !== []) {
            $this->newLine();
            $this->warn('Merged rows with tare null, zero, or negative (from spreadsheet):');
            $this->comment('Tare (database) is the value before this run (new row = —).');
            $this->table(
                [
                    'Vehicle workorder ID',
                    'Vehicle no.',
                    'Tare (XLSX)',
                    'Tare (database)',
                    'Outcome',
                ],
                array_map(
                    fn (array $row): array => [
                        (string) $row['vehicle_workorder_id'],
                        $row['vehicle_no'],
                        self::formatTareForCli($row['tare_weight_xlsx']),
                        self::formatTareForCli($row['tare_weight_database']),
                        mb_strtoupper($row['outcome']),
                    ],
                    $stats['tare_weight_issue_rows'],
                ),
            );
        }

        return self::SUCCESS;
    }

    private static function formatTareForCli(?float $value): string
    {
        return $value === null ? '—' : (string) $value;
    }
}
