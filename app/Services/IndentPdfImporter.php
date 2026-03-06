<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Indent;
use App\Models\Siding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\PdfToText\Pdf;
use Throwable;

final readonly class IndentPdfImporter
{
    /**
     * Import a historical e-Demand Confirmation Slip from an uploaded PDF.
     */
    public function import(UploadedFile $pdf, int $userId): Indent
    {
        Log::info('Indent PDF import: starting', [
            'user_id' => $userId,
            'original_name' => $pdf->getClientOriginalName(),
            'size' => $pdf->getSize(),
        ]);

        $absolutePath = $pdf->getRealPath();
        $text = Pdf::getText($absolutePath, null, ['-layout']);

        return $this->importFromText($text, $pdf, $userId);
    }

    /**
     * Import from already-extracted PDF text (e.g. for testing).
     */
    public function importFromText(string $text, UploadedFile $pdf, int $userId): Indent
    {
        $parsed = $this->parsePdfText($text);
        $siding = $this->detectSiding($text, $parsed);
        if (! $siding) {
            throw new InvalidArgumentException(
                'Unable to detect siding from indent PDF. Ensure "Station From" (e.g. WBPC, DMK, PKR) or siding name is present.'
            );
        }

        Log::info('Indent PDF import: parsed and siding detected', [
            'user_id' => $userId,
            'indent_number' => $parsed['indent_number'] ?? null,
            'e_demand_reference_id' => $parsed['e_demand_reference_id'] ?? null,
            'siding_id' => $siding->id,
        ]);

        try {
            return DB::transaction(function () use ($parsed, $siding, $pdf, $userId) {
                $indent = Indent::query()->create([
                    'siding_id' => $siding->id,
                    'indent_number' => $parsed['indent_number'] ?? null,
                    'demanded_stock' => $parsed['demanded_stock'] ?? null,
                    'total_units' => $parsed['total_units'] !== null ? (int) $parsed['total_units'] : null,
                    'target_quantity_mt' => $parsed['target_quantity_mt'] ?? null,
                    'allocated_quantity_mt' => null,
                    'available_stock_mt' => null,
                    'indent_date' => $parsed['indent_date'] ?? null,
                    'indent_time' => $parsed['indent_time'] ?? null,
                    'expected_loading_date' => $parsed['expected_loading_date'] ?? null,
                    'required_by_date' => null,
                    'railway_reference_no' => $parsed['railway_reference_no'] ?? null,
                    'e_demand_reference_id' => $parsed['e_demand_reference_id'] ?? null,
                    'fnr_number' => $parsed['fnr_number'] ?? null,
                    'state' => 'historical_import',
                    'remarks' => $parsed['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $indent->addMedia($pdf)->toMediaCollection('indent_pdf');

                Log::info('Indent PDF import: transaction committed', [
                    'user_id' => $userId,
                    'indent_id' => $indent->id,
                ]);

                return $indent->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Indent PDF import: transaction rolled back', [
                'user_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePdfText(string $text): array
    {
        $out = [];

        if (preg_match('/e-Demand\s+Reference\s+Id\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['e_demand_reference_id'] = mb_trim($m[1]);
        }
        if (preg_match('/Forwarding\s+Note\s+Number\s*[:\-]\s*([\d.]+)/i', $text, $m)) {
            $out['indent_number'] = mb_trim($m[1]);
        }
        if (preg_match('/Priority\s+Class\s*\/\s*Number\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|\n|$)/s', $text, $m)) {
            $out['railway_reference_no'] = mb_trim($m[1]);
        }
        if (preg_match('/FNR\s+Number\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['fnr_number'] = mb_trim($m[1]);
        }

        $demandedStockMatches = [];
        if (preg_match_all('/Demanded\s+Stock\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $demandedStockMatches = array_merge($demandedStockMatches, $m[1]);
        }
        if (preg_match_all('/Stock\s+Demanded\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $demandedStockMatches = array_merge($demandedStockMatches, $m[1]);
        }
        $invalidStockValues = ['units', 'units:'];
        $validStock = array_values(array_filter($demandedStockMatches, function (string $v) use ($invalidStockValues): bool {
            $v = mb_strtolower(mb_trim($v));

            return $v !== '' && ! in_array($v, $invalidStockValues, true);
        }));
        if ($validStock !== []) {
            $out['demanded_stock'] = mb_trim($validStock[count($validStock) - 1]);
        }

        $unitsMatches = [];
        if (preg_match_all('/Total\s+Units\s*[:\-]\s*(\d+)/i', $text, $m)) {
            $unitsMatches = array_merge($unitsMatches, $m[1]);
        }
        if (preg_match_all('/Units\s*[:\-]\s*(\d+)/i', $text, $m)) {
            $unitsMatches = array_merge($unitsMatches, $m[1]);
        }
        if ($unitsMatches !== []) {
            $out['total_units'] = (int) $unitsMatches[count($unitsMatches) - 1];
        }

        if (preg_match_all('/Weight\s*\(In\s*Tonnes\)\s*[:\-]?\s*[\r\n\s]*([\d.,]+)/i', $text, $m)) {
            $out['target_quantity_mt'] = $this->toFloat($m[1][count($m[1]) - 1]);
        }

        if (preg_match('/Demand\s+Date\/Time\s*[:\-]\s*([0-9\-:\s]+)/i', $text, $m)) {
            $dt = $this->parseDate(mb_trim($m[1]));
            $out['indent_date'] = $dt;
            $out['indent_time'] = $dt;
        }
        if (preg_match('/Expected\s+Loading\s+Date\s*[:\-]\s*([0-9\-]+)/i', $text, $m)) {
            $out['expected_loading_date'] = $this->parseDate(mb_trim($m[1]));
        }

        if (preg_match('/Station\s+From\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['station_from'] = mb_trim($m[1]);
        }
        if (preg_match('/Destination\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|\n\n|$)/s', $text, $m)) {
            $out['destination'] = mb_trim($m[1]);
        }
        if (preg_match('/Booking\s+Remark\s*[:\-]\s*(.+?)(?=\n\n|\n[A-Z][a-z]+[\s:]|$)/s', $text, $m)) {
            $out['booking_remark'] = mb_trim($m[1]);
        }

        $remarks = [];
        if (! empty($out['destination'])) {
            $remarks[] = 'Destination: '.$out['destination'];
        }
        if (! empty($out['booking_remark'])) {
            $remarks[] = 'Booking Remark: '.$out['booking_remark'];
        }
        $out['remarks'] = implode(' ', $remarks) ?: null;

        return $out;
    }

    private function detectSiding(string $text, array $parsed): ?Siding
    {
        $stationFrom = $parsed['station_from'] ?? null;
        if ($stationFrom !== null && $stationFrom !== '') {
            $siding = Siding::query()
                ->where('station_code', $stationFrom)
                ->orWhere('code', $stationFrom)
                ->first();
            if ($siding !== null) {
                return $siding;
            }
        }

        $upper = mb_strtoupper($text);
        $keywords = ['DMK' => 'DMK', 'DUMK' => 'DMK', 'KRW' => 'KRW', 'KURWA' => 'KRW', 'PKR' => 'PKR', 'PKUR' => 'PKUR', 'WBPC' => 'WBPC'];
        foreach ($keywords as $needle => $stationCode) {
            if (str_contains($upper, $needle)) {
                $siding = Siding::query()
                    ->where('station_code', $stationCode)
                    ->orWhere('code', $stationCode)
                    ->first();
                if ($siding !== null) {
                    return $siding;
                }
            }
        }

        return $this->detectSidingByName($upper);
    }

    private function detectSidingByName(string $textUpper): ?Siding
    {
        $sidings = Siding::query()->where('is_active', true)->get();
        foreach ($sidings as $siding) {
            $nameUpper = mb_strtoupper(mb_trim($siding->name ?? ''));
            if ($nameUpper === '') {
                continue;
            }
            if (str_contains($textUpper, $nameUpper)) {
                return $siding;
            }
            $firstWord = explode(' ', $nameUpper)[0] ?? '';
            if ($firstWord !== '' && mb_strlen($firstWord) >= 3 && str_contains($textUpper, $firstWord)) {
                return $siding;
            }
        }

        return null;
    }

    private function parseDate(?string $value): ?\Carbon\CarbonInterface
    {
        if ($value === null || mb_trim($value) === '') {
            return null;
        }
        try {
            return Date::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $value = str_replace([','], '', (string) $value);
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
