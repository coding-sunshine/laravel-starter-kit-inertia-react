<?php

declare(strict_types=1);

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Header and cell coercion for vehicle workorders XLSX import ({@see \App\Models\VehicleWorkorder}).
 */
final class VehicleWorkorderSpreadsheetNormalizer
{
    public static function normalizeHeaderLabel(mixed $headerCell): string
    {
        return TransportWorkOrderRegistrationSpreadsheetNormalizer::normalizeHeaderLabel($headerCell);
    }

    /**
     * Registration number / truck identifier: trimmed, internal whitespace collapsed, uppercased.
     */
    public function normalizeVehicleNo(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $s = is_string($raw) || is_numeric($raw) ? mb_trim((string) $raw) : '';
        if ($s === '') {
            return null;
        }

        $collapsed = mb_trim((string) preg_replace('/\s+/u', ' ', $s));

        return $collapsed !== '' ? mb_strtoupper($collapsed) : null;
    }

    /**
     * @param  mixed  $raw  Excel serial (float/int/string numeric), ISO-ish date string, or placeholders.
     */
    public function normalizeDate(mixed $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        $s = mb_trim((string) $raw);
        $trimLower = mb_strtolower(mb_trim((string) preg_replace('/\s+/u', ' ', $s)));

        if (self::dateMeansUnset($trimLower)) {
            return null;
        }

        $parsed = strtotime($s);

        if ($parsed !== false) {
            return date('Y-m-d', $parsed);
        }

        return null;
    }

    /**
     * Use the spreadsheet cell value as-is (trimmed): no rounding. The DB column may still enforce scale.
     */
    public function parseTareWeight(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $raw = mb_trim($raw);
        }

        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    public function normalizeTyres(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $raw = mb_trim($raw);
        }
        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $float = (float) $raw;
        $int = (int) round($float);
        if ($int < 0) {
            return null;
        }

        return abs($float - round($float)) <= 1e-6 ? $int : null;
    }

    public function nullableStringCell(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw) && (string) $raw !== '') {
            $float = (float) $raw;
            if ($float === floor($float)) {
                return (string) (int) $float;
            }

            return mb_trim((string) $float);
        }

        $s = mb_trim((string) $raw);

        return $s !== '' ? $s : null;
    }

    private static function dateMeansUnset(?string $trimmedLower): bool
    {
        if ($trimmedLower === '') {
            return true;
        }

        if (preg_match('/^(not\s+issued|---|–|-|n\/a|\s*)$/iu', $trimmedLower) === 1) {
            return true;
        }

        return str_contains(mb_strtolower($trimmedLower), 'not issued');
    }
}
