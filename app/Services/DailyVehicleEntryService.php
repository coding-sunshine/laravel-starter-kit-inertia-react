<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CoalStockUpdated;
use App\Jobs\NotifySuperAdmins;
use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\StockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class DailyVehicleEntryService
{
    private const STOCK_REQUIREMENT_MT_PER_RAKE = 3500;

    /**
     * @param  int|null  $sidingId  When set (e.g. from SidingContext), only entries for this siding are returned so the list matches the user's context.
     * @param  string|null  $entryType  When set, only entries of this type are returned (e.g. 'railway_siding_empty_weighment').
     * @param  int|null  $createdByUserId  When set, only entries created by this user (road dispatch operator scoping).
     */
    public function getEntriesByDateAndShift(string $date, int $shift, ?int $sidingId = null, ?string $entryType = null, ?int $createdByUserId = null): Collection
    {
        $query = DailyVehicleEntry::query()
            ->with(['siding', 'creator', 'updater'])
            ->where('entry_date', $date)
            ->where('shift', $shift)
            ->orderBy('created_at', 'asc');

        if ($sidingId !== null) {
            $query->where('siding_id', $sidingId);
        }

        if ($entryType !== null) {
            $query->where('entry_type', $entryType);
        }

        if ($createdByUserId !== null) {
            $query->where('created_by', $createdByUserId);
        }

        return $query->get();
    }

    public function createEntry(array $data): DailyVehicleEntry
    {
        
        $entryType = $data['entry_type'] ?? DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH;

        return DailyVehicleEntry::create([
            ...$data,
            'entry_type' => $entryType,
            'reached_at' => $data['reached_at'] ?? now(),
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
            $oldNetWt = (float) ($entry->net_wt ?? 0);
            $entry->update([
                ...$data,
                'status' => $newStatus,
                'updated_by' => auth()->id(),
            ]);

            if ($oldStatus === 'completed' && $newStatus === 'draft') {
                $this->deleteStockLedgerEntry($entry);
            }

            if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                $this->createStockLedgerEntry($entry->fresh());
            }

            if ($oldStatus === 'completed' && $newStatus === 'completed') {
                $this->updateStockLedgerWithDelta($entry->fresh(), $oldNetWt);
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

    /**
     * @param  string|null  $entryType  When set, count only entries of this type.
     * @param  int|null  $sidingId  When set, restrict counts to this siding.
     * @param  int|null  $createdByUserId  When set, count only rows created by this user.
     */
    public function getShiftSummary(string $date, ?string $entryType = null, ?int $sidingId = null, ?int $createdByUserId = null): array
    {
        $summary = [];

        for ($shift = 1; $shift <= 3; $shift++) {
            $query = DailyVehicleEntry::query()
                ->where('entry_date', $date)
                ->where('shift', $shift);

            if ($entryType !== null) {
                $query->where('entry_type', $entryType);
            }

            if ($sidingId !== null) {
                $query->where('siding_id', $sidingId);
            }

            if ($createdByUserId !== null) {
                $query->where('created_by', $createdByUserId);
            }

            $summary[$shift] = $query->count();
        }

        return $summary;
    }

    /**
     * @param  string|null  $entryType  When set, export only entries of this type (e.g. 'railway_siding_empty_weighment').
     */
    public function exportEntries(string $date, int $sidingId, string $shift, ?string $entryType = null, ?int $createdByUserId = null): string
    {
        $siding = Siding::findOrFail($sidingId);

        if ($shift === 'all') {
            return $this->exportAllShifts($date, $siding, $entryType, $createdByUserId);
        }

        return $this->exportSingleShift($date, $siding, (int) $shift, $entryType, $createdByUserId);
    }

    /**
     * Create a stock_ledgers receipt row for this completed daily vehicle entry.
     * Opening balance: when no ledger row exists for this siding, opening = 0 (first ever transaction).
     * Otherwise opening = previous row's closing_balance_mt (running balance).
     * Skipped for entry_type other than road_dispatch (e.g. railway_siding_empty_weighment).
     */
    private function createStockLedgerEntry(DailyVehicleEntry $entry): void
    {
        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH) {
            return;
        }
    
        $grossWt = (float) ($entry->gross_wt ?? 0);
        $tareWt = (float) ($entry->tare_wt ?? 0);
        $netWeight = round($grossWt - $tareWt, 2);
    
        if ($netWeight <= 0) {
            $entry->update(['net_wt' => null]);
            return;
        }
    
        $closingBalance = 0;
        $openingBalance = 0;
    
        DB::transaction(function () use ($entry, $netWeight, &$openingBalance, &$closingBalance) {
    
            $lastLedger = StockLedger::query()
                ->where('siding_id', $entry->siding_id)
                ->lockForUpdate()
                ->latest('id')
                ->first();
    
            $openingBalance = $lastLedger
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($entry->siding_id);
    
            $closingBalance = round($openingBalance + $netWeight, 2);
    
            StockLedger::create([
                'siding_id' => $entry->siding_id,
                'transaction_type' => 'receipt',
                'daily_vehicle_entry_id' => $entry->id,
                'quantity_mt' => $netWeight,
                'opening_balance_mt' => $openingBalance,
                'closing_balance_mt' => $closingBalance,
                'reference_number' => $entry->e_challan_no,
                'remarks' => "Vehicle {$entry->vehicle_no} — Entry #{$entry->id}",
                'created_by' => auth()->id(),
            ]);
    
            //  use model, not query
            $entry->update(['net_wt' => $netWeight]);
        });
    
        // after transaction (safe to use values now)
        $req = self::STOCK_REQUIREMENT_MT_PER_RAKE;
    
        if ($req > 0) {
            $oldCapacity = (int) floor($openingBalance / $req);
            $newCapacity = (int) floor($closingBalance / $req);
    
            if ($newCapacity > 0 && $newCapacity > $oldCapacity) {
    
                $sidingName = Siding::query()
                    ->whereKey($entry->siding_id)
                    ->value('name');
    
                DB::afterCommit(function () use ($entry, $closingBalance, $newCapacity, $req, $sidingName) {
                    NotifySuperAdmins::dispatch(
                        \App\Notifications\StockCapacityIncreasedNotification::class,
                        [
                            'siding_id' => (int) $entry->siding_id,
                            'siding_name' => $sidingName,
                            'closing_balance_mt' => $closingBalance,
                            'capacity_rakes' => $newCapacity,
                            'requirement_mt' => $req,
                        ]
                    );
                });
            }
        }
    }

    private function shouldAutoComplete(array $data, DailyVehicleEntry $entry): bool
    {
        if ($entry->entry_type === DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            $tareWtTwo = (float) ($data['tare_wt_two'] ?? $entry->tare_wt_two ?? 0);
            $vehicleNo = mb_trim((string) ($data['vehicle_no'] ?? $entry->vehicle_no ?? ''));

            return $tareWtTwo > 0 && $vehicleNo !== '';
        }

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

        return $data['status'] ?? $entry->status ?? 'draft';
    }

    private function updateStockLedgerWithDelta(
        DailyVehicleEntry $entry,
        float $oldWeight
    ): void {
        $grossWt = (float) ($entry->gross_wt ?? 0);
        $tareWt = (float) ($entry->tare_wt ?? 0);
    
        $newWeight = round($grossWt - $tareWt, 2);
        $delta = round($newWeight - $oldWeight, 2);
    
        // nothing changed
        if ($delta == 0) {
            return;
        }
    
        DB::transaction(function () use ($entry, $delta, $newWeight) {
    
            $lastLedger = StockLedger::query()
                ->where('siding_id', $entry->siding_id)
                ->lockForUpdate()
                ->latest('id')
                ->first();
    
            $opening = $lastLedger
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($entry->siding_id);
    
            $closing = round($opening + $delta, 2);
    
            StockLedger::create([
                'siding_id' => $entry->siding_id,
                'transaction_type' => 'correction',
                'daily_vehicle_entry_id' => $entry->id,
                'quantity_mt' => $delta, // 🔥 delta applied
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'reference_number' => $entry->e_challan_no,
                'remarks' => "Correction for entry #{$entry->id}",
                'created_by' => auth()->id(),
            ]);
    
            // ✅ update final value
            $entry->update(['net_wt' => $newWeight]);
        });
    }

    private function deleteStockLedgerEntry(DailyVehicleEntry $entry): void
    {
        $query = StockLedger::query()->where('siding_id', $entry->siding_id)
            ->where('transaction_type', 'receipt')
            ->where('daily_vehicle_entry_id', $entry->id)
            ->orWhere('reference_number', $entry->e_challan_no);

        $query->delete();

        if ($entry->entry_type === DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH) {
            DailyVehicleEntry::query()->whereKey($entry->id)->update(['net_wt' => null]);
        }
    }

    private function exportAllShifts(string $date, Siding $siding, ?string $entryType = null, ?int $createdByUserId = null): string
    {
        $filename = "{$siding->name}_{$date}_AllShifts.xlsx";
        $filepath = storage_path("app/public/{$filename}");

        $handle = fopen($filepath, 'w');

        // Create HTML table that Excel can open
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.$filename.'</title></head><body>';
        $html .= '<table border="1">';

        // Get entries for all shifts
        $shift1Entries = $this->getEntriesForExport($date, $siding->id, 1, $entryType, $createdByUserId);
        $shift2Entries = $this->getEntriesForExport($date, $siding->id, 2, $entryType, $createdByUserId);
        $shift3Entries = $this->getEntriesForExport($date, $siding->id, 3, $entryType, $createdByUserId);

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

    private function exportSingleShift(string $date, Siding $siding, int $shift, ?string $entryType = null, ?int $createdByUserId = null): string
    {
        $filename = "{$siding->name}_{$date}_Shift{$shift}.xlsx";
        $filepath = storage_path("app/public/{$filename}");

        $handle = fopen($filepath, 'w');

        // Create HTML table that Excel can open
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.$filename.'</title></head><body>';
        $html .= '<table border="1">';

        $entries = $this->getEntriesForExport($date, $siding->id, $shift, $entryType, $createdByUserId);

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

    private function getEntriesForExport(string $date, int $sidingId, int $shift, ?string $entryType = null, ?int $createdByUserId = null): Collection
    {
        $query = DailyVehicleEntry::query()
            ->where('entry_date', $date)
            ->where('siding_id', $sidingId)
            ->where('shift', $shift)
            ->orderBy('reached_at', 'asc');

        if ($entryType !== null) {
            $query->where('entry_type', $entryType);
        }

        if ($createdByUserId !== null) {
            $query->where('created_by', $createdByUserId);
        }

        return $query->get();
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
