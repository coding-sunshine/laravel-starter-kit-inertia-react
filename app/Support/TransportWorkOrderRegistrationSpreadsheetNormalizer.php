<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TransportWorkOrderRegistration;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Converts spreadsheet strings/cells into values persisted on {@see TransportWorkOrderRegistration}.
 */
final class TransportWorkOrderRegistrationSpreadsheetNormalizer
{
    /**
     * @used-by Actions resolving header labels from XLSX first row.
     */
    public static function normalizeHeaderLabel(mixed $headerCell): string
    {
        return mb_strtolower(mb_trim((string) preg_replace('/\s+/u', ' ', (string) $headerCell)));
    }

    public function normalizeReferenceNo(mixed $raw): ?string
    {
        $s = $this->normalizeWhitespaceString($raw);
        if ($s === null || $s === '') {
            return null;
        }

        $lower = mb_strtolower($s);
        if (self::referenceMeansUnset($lower)) {
            return null;
        }

        return $s;
    }

    /**
     * @return array{is_active: bool, status: ?string}
     */
    public function normalizeStatusColumns(?string $raw): array
    {
        $s = $this->normalizeWhitespaceString($raw ?? '');
        if ($s === null || $s === '') {
            return ['is_active' => true, 'status' => null];
        }

        $upper = mb_strtoupper($s);

        if ($upper === 'ACTIVE') {
            return ['is_active' => true, 'status' => $s];
        }

        if ($upper === 'INACTIVE') {
            return ['is_active' => false, 'status' => $s];
        }

        return ['is_active' => true, 'status' => $s];
    }

    /**
     * Returns canonical {@see TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_VALUES} value or null.
     */
    public function normalizeGraminOrNonGramin(mixed $raw): ?string
    {
        $s = $this->normalizeWhitespaceString($raw);
        if ($s === null || $s === '') {
            return null;
        }

        $collapsed = mb_strtolower(mb_trim((string) preg_replace('/\s+/u', ' ', $s)));

        if ($collapsed === 'non gramin') {
            return TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_NON_GRAMIN;
        }

        if ($collapsed === 'gramin') {
            return TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_GRAMIN;
        }

        if ($collapsed === 'surrendered') {
            return null;
        }

        return null;
    }

    /**
     * @param  mixed  $raw  Excel serial (float/int/string numeric), ISO-ish date string, or placeholder text.
     */
    public function normalizeWorkOrderDate(mixed $raw): ?string
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
     * @see normalizeOptionalString Trim only; numeric cells become compact string without useless decimals.
     */
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

    /**
     * Reference placeholders treated as “no ref”.
     */
    private static function referenceMeansUnset(?string $trimmedLower): bool
    {
        if ($trimmedLower === '') {
            return true;
        }

        if ($trimmedLower === mb_strtolower('NEW WORKORDER NOT ISSUED')) {
            return true;
        }

        return str_contains($trimmedLower, 'new workorder') && str_contains($trimmedLower, 'not issued');
    }

    /**
     * Date placeholders (no calendar date issued).
     */
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

    private function normalizeWhitespaceString(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $s = mb_trim((string) $raw);

        return $s !== '' ? $s : null;
    }
}
