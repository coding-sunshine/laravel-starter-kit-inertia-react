<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CoalStockUpdated;
use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\StockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class DailyVehicleEntryService
{
    /**
     * @param  int|null  $sidingId  When set (e.g. from SidingContext), only entries for this siding are returned so the list matches the user's context.
     */
    public function getEntriesByDateAndShift(string $date, int $shift, ?int $sidingId = null): Collection
    {
        $query = DailyVehicleEntry::query()
            ->with(['siding', 'creator', 'updater'])
            ->where('entry_date', $date)
            ->where('shift', $shift)
            ->orderBy('created_at', 'asc');

        if ($sidingId !== null) {
            $query->where('siding_id', $sidingId);
        }

        return $query->get();
    }

    public function createEntry(array $data): DailyVehicleEntry
    {
        return DailyVehicleEntry::create([
            ...$data,
            'reached_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    public function updateEntry(DailyVehicleEntry $entry, array $data): DailyVehicleEntry
    {
        $oldStatus = $entry->status;
        $newStatus = $this->determineStatus($entry, $data);

        $ledgerChanged = ($oldStatus === 'completed' && $newStatus === 'draft')
            || ($oldStatus !== 'completed' && $newStatus === 'completed')
            || ($oldStatus === 'completed' && $newStatus === 'completed');

        $updated = DB::transaction(function () use ($entry, $data, $oldStatus, $newStatus): DailyVehicleEntry {
            $entry->update([
                ...$data,
                'status' => $newStatus,
                'updated_by' => auth()->id(),
            ]);

            if ($oldStatus === 'completed' && $newStatus === 'draft') {
                $this->deleteStockLedgerEntry($entry);
            }

            if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                $this->createStockLedgerEntry($entry);
            }

            if ($oldStatus === 'completed' && $newStatus === 'completed') {
                $this->deleteStockLedgerEntry($entry);
                $this->createStockLedgerEntry($entry->fresh());
            }

            return $entry->fresh();
        });

        if ($ledgerChanged) {
            $balance = (float) (StockLedger::query()
                ->where('siding_id', $updated->siding_id)
                ->latest('id')
                ->value('closing_balance_mt') ?? 0);
            event(new CoalStockUpdated($updated->siding_id, $balance));
        }

        return $updated;
    }

    public function markCompleted(DailyVehicleEntry $entry): DailyVehicleEntry
    {
        return $this->updateEntry($entry, []);
    }

    public function getShiftSummary(string $date): array
    {
        $summary = [];

        for ($shift = 1; $shift <= 3; $shift++) {
            $count = DailyVehicleEntry::where('entry_date', $date)
                ->where('shift', $shift)
                ->count();

            $summary[$shift] = $count;
        }

        return $summary;
    }

    public function exportEntries(string $date, int $sidingId, string $shift): string
    {
        $siding = Siding::findOrFail($sidingId);

        if ($shift === 'all') {
            return $this->exportAllShifts($date, $siding);
        }

        return $this->exportSingleShift($date, $siding, (int) $shift);

    }

    /**
     * Create a stock_ledgers receipt row for this completed daily vehicle entry.
     * Opening balance: when no ledger row exists for this siding, opening = 0 (first ever transaction).
     * Otherwise opening = previous row's closing_balance_mt (running balance).
     */
    private function createStockLedgerEntry(DailyVehicleEntry $entry): void
    {
        $grossWt = (float) ($entry->gross_wt ?? 0);
        $tareWt = (float) ($entry->tare_wt ?? 0);
        $netWeight = round($grossWt - $tareWt, 2);

        if ($netWeight <= 0) {
            return;
        }

        $lastLedger = StockLedger::query()
            ->where('siding_id', $entry->siding_id)
            ->latest('id')
            ->first();

        // No prior ledger for this siding => opening 0 (first transaction). Else use last row's closing.
        $openingBalance = $lastLedger
            ? (float) $lastLedger->closing_balance_mt
            : 0.0;

        StockLedger::create([
            'siding_id' => $entry->siding_id,
            'transaction_type' => 'receipt',
            'vehicle_arrival_id' => null,
            'rake_id' => null,
            'daily_vehicle_entry_id' => $entry->id,
            'quantity_mt' => $netWeight,
            'opening_balance_mt' => $openingBalance,
            'closing_balance_mt' => round($openingBalance + $netWeight, 2),
            'reference_number' => $entry->e_challan_no ?? "DVE-{$entry->id}",
            'remarks' => "Vehicle {$entry->vehicle_no} — Daily entry #{$entry->id}, Shift {$entry->shift}",
            'created_by' => auth()->id(),
        ]);
    }

    private function shouldAutoComplete(array $data, DailyVehicleEntry $entry): bool
    {
        $grossWt = (float) ($data['gross_wt'] ?? $entry->gross_wt ?? 0);
        $tareWt = (float) ($data['tare_wt'] ?? $entry->tare_wt ?? 0);
        $netWeight = round($grossWt - $tareWt, 2);

        return $grossWt > 0 && $tareWt > 0 && $netWeight > 0;
    }

    private function determineStatus(DailyVehicleEntry $entry, array $data): string
    {
        if ($this->shouldAutoComplete($data, $entry)) {
            return 'completed';
        }

        return 'draft';
    }

    private function deleteStockLedgerEntry(DailyVehicleEntry $entry): void
    {
        $query = StockLedger::query()->where('siding_id', $entry->siding_id)
            ->where('transaction_type', 'receipt');

        $query->where(function ($q) use ($entry): void {
            $q->where('daily_vehicle_entry_id', $entry->id)
                ->orWhere(function ($q2) use ($entry): void {
                    $q2->where('reference_number', $entry->e_challan_no ?? "DVE-{$entry->id}")
                        ->where('remarks', 'LIKE', "%Daily entry #{$entry->id}%");
                });
        });

        $query->delete();
    }

    private function exportAllShifts(string $date, Siding $siding): string
    {
        $filename = "{$siding->name}_{$date}_AllShifts.xlsx";
        $filepath = storage_path("app/public/{$filename}");

        $handle = fopen($filepath, 'w');

        // Create HTML table that Excel can open
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.$filename.'</title></head><body>';
        $html .= '<table border="1">';

        // Get entries for all shifts
        $shift1Entries = $this->getEntriesForExport($date, $siding->id, 1);
        $shift2Entries = $this->getEntriesForExport($date, $siding->id, 2);
        $shift3Entries = $this->getEntriesForExport($date, $siding->id, 3);

        $maxRows = max(count($shift1Entries), count($shift2Entries), count($shift3Entries));

        // Write shift headers (Row 1)
        $html .= '<tr>';
        $shiftNames = ['1ST SHIFT', '2ND SHIFT', '3RD SHIFT'];
        for ($i = 0; $i < 3; $i++) {
            $html .= '<td colspan="10" style="font-weight:bold; text-align:center; background-color:#f0f0f0;">'.$shiftNames[$i].'</td>';
        }
        $html .= '</tr>';

        // Write column headers (Row 2)
        $headers = ['SL NO', 'E CHALLAN NO', 'VEHICLE NO', 'GROSS WT', 'TARE WT', 'REACHED AT', 'WB NO', 'D-CHALLAN NO', 'CHALLAN MODE', 'STATUS'];
        $html .= '<tr>';
        for ($i = 0; $i < 3; $i++) {
            foreach ($headers as $header) {
                $html .= '<td style="font-weight:bold; background-color:#e0e0e0; border:1px solid #ccc;">'.$header.'</td>';
            }
        }
        $html .= '</tr>';

        // Write data rows
        for ($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++) {
            $html .= '<tr>';

            // Shift 1 data
            $shift1Data = $this->getShiftData($shift1Entries, $rowIndex);
            foreach ($shift1Data as $cell) {
                $html .= '<td style="border:1px solid #ccc;">'.$cell.'</td>';
            }

            // Shift 2 data
            $shift2Data = $this->getShiftData($shift2Entries, $rowIndex);
            foreach ($shift2Data as $cell) {
                $html .= '<td style="border:1px solid #ccc;">'.$cell.'</td>';
            }

            // Shift 3 data
            $shift3Data = $this->getShiftData($shift3Entries, $rowIndex);
            foreach ($shift3Data as $cell) {
                $html .= '<td style="border:1px solid #ccc;">'.$cell.'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        fwrite($handle, $html);
        fclose($handle);

        return $filepath;
    }

    private function exportSingleShift(string $date, Siding $siding, int $shift): string
    {
        $filename = "{$siding->name}_{$date}_Shift{$shift}.xlsx";
        $filepath = storage_path("app/public/{$filename}");

        $handle = fopen($filepath, 'w');

        // Create HTML table that Excel can open
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.$filename.'</title></head><body>';
        $html .= '<table border="1">';

        $entries = $this->getEntriesForExport($date, $siding->id, $shift);

        // Write title row
        $html .= '<tr><td colspan="10" style="font-weight:bold; text-align:center; background-color:#f0f0f0;">SHIFT '.$shift.' - '.$date.' - '.$siding->name.'</td></tr>';

        // Write headers
        $headers = ['SL NO', 'E CHALLAN NO', 'VEHICLE NO', 'GROSS WT', 'TARE WT', 'REACHED AT', 'WB NO', 'D-CHALLAN NO', 'CHALLAN MODE', 'STATUS'];
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<td style="font-weight:bold; background-color:#e0e0e0; border:1px solid #ccc;">'.$header.'</td>';
        }
        $html .= '</tr>';

        // Write data
        foreach ($entries as $index => $entry) {
            $row = [
                $index + 1, // SL NO
                $entry->e_challan_no ?? '',
                $entry->vehicle_no ?? '',
                $entry->gross_wt ?? '',
                $entry->tare_wt ?? '',
                $entry->reached_at?->format('Y-m-d H:i:s') ?? '',
                $entry->wb_no ?? '',
                $entry->d_challan_no ?? '',
                $entry->challan_mode ?? '',
                $entry->status ?? '',
            ];

            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td style="border:1px solid #ccc;">'.$cell.'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        fwrite($handle, $html);
        fclose($handle);

        return $filepath;
    }

    private function getEntriesForExport(string $date, int $sidingId, int $shift): Collection
    {
        return DailyVehicleEntry::query()
            ->where('entry_date', $date)
            ->where('siding_id', $sidingId)
            ->where('shift', $shift)
            ->orderBy('reached_at', 'asc')
            ->get();
    }

    private function getShiftData(Collection $entries, int $rowIndex): array
    {
        if ($rowIndex < $entries->count()) {
            $entry = $entries[$rowIndex];
            $slNo = $rowIndex + 1;

            return [
                $slNo,
                $entry->e_challan_no ?? '',
                $entry->vehicle_no ?? '',
                $entry->gross_wt ?? '',
                $entry->tare_wt ?? '',
                $entry->reached_at?->format('Y-m-d H:i:s') ?? '',
                $entry->wb_no ?? '',
                $entry->d_challan_no ?? '',
                $entry->challan_mode ?? '',
                $entry->status ?? '',
            ];
        }

        // Return empty cells
        return array_fill(0, 10, '');

    }
}
