<?php

declare(strict_types=1);

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Thin wrapper around Maatwebsite\Excel/PhpSpreadsheet so seeders and other
 * app-layer code don't reach into those vendor namespaces directly.
 */
final readonly class ExcelWorkbookReader
{
    /**
     * @return array<int, array<int, array<int, mixed>>>
     */
    public function toArray(string $path): array
    {
        return Excel::toArray([], $path);
    }

    /**
     * Convert an Excel date serial number (or date string) to a `Y-m-d` string.
     */
    public function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
        if (is_string($value)) {
            $parsed = strtotime(mb_trim($value));

            return $parsed !== false ? date('Y-m-d', $parsed) : null;
        }

        return null;
    }
}
