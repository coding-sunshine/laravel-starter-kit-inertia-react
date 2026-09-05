<?php

declare(strict_types=1);

namespace App\Services;

use App\Imports\HistoricalRakeImport;
use Maatwebsite\Excel\Facades\Excel;

final readonly class HistoricalRakeImportService
{
    /**
     * Import historical rake data from the given Excel file into the given siding.
     */
    public function handle(string $file, int $sidingId): void
    {
        Excel::import(new HistoricalRakeImport($sidingId), $file);
    }
}
