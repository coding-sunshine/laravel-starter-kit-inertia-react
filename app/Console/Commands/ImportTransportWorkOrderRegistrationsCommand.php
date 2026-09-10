<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportTransportWorkOrderRegistrationsFromExcelAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

final class ImportTransportWorkOrderRegistrationsCommand extends Command
{
    protected $signature = 'transport-work-order-registrations:import-excel
                            {--file=database/excel/transpoter.xlsx : Path to the XLSX file}
                            {--dry-run : Parse and show values that would be stored; no database writes}';

    protected $description = 'Import transport work order registrations from spreadsheet (upsert by Work Order No. 2).';

    public function handle(ImportTransportWorkOrderRegistrationsFromExcelAction $action): int
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
            $this->info(sprintf(
                'Summary — would create: %d, would update: %d, spreadsheet rows skipped (missing WO2): %d.',
                $preview['stats']['would_create'],
                $preview['stats']['would_update'],
                $preview['stats']['skipped'],
            ));
            $this->comment('Create/update totals are per unique Work Order No. 2 after duplicate rows are merged (last row wins — same rules as import). Live import increments created/updated once per spreadsheet row.');
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
                    $row['work_order_no_2'],
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
                $this->comment('No rows with a Work Order No. 2 to import.');
            }

            return self::SUCCESS;
        }

        try {
            $stats = $action->handle($path);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Import finished — created: %d, updated: %d, skipped: %d.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
