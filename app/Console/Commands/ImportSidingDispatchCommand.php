<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportSidingDispatchFromExcel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ImportSidingDispatchCommand extends Command
{
    protected $signature = 'historical-mines:import-siding-dispatch
                            {--file=database/excel/siding dispatched.xlsx : Path to the XLSX file to import}';

    protected $description = 'Import database/excel/siding dispatched.xlsx into historical_mines (per-siding month rows).';

    public function handle(ImportSidingDispatchFromExcel $importer): int
    {
        $file = (string) $this->option('file');
        $path = str_starts_with($file, '/') ? $file : base_path($file);

        if (! File::exists($path)) {
            $this->error(sprintf('File not found: %s', $path));

            return self::FAILURE;
        }

        $stats = $importer->handle($path);

        $this->info('Siding dispatch import completed.');

        $this->table(
            ['months_processed', 'created', 'siding_columns_mapped', 'errors'],
            [[
                $stats['months_processed'],
                $stats['created'],
                $stats['siding_columns_mapped'],
                $stats['errors'],
            ]],
        );

        if (($stats['errors'] ?? 0) > 0) {
            $this->newLine();
            $this->warn('Errors:');
            foreach ($stats['error_messages'] as $message) {
                $this->line('- '.$message);
            }
        }

        return self::SUCCESS;
    }
}
