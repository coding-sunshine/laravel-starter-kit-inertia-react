<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\CoalStockUpdated;
use App\Jobs\NotifySuperAdmins;
use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\StockLedger;
use App\Models\VehicleArrival;
use App\Models\VehicleUnload;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * UpdateStockLedger - Record and update coal stock movements
 *
 * Handles all stock transactions:
 * - Receipts: Coal arriving via vehicle
 * - Dispatches: Coal leaving via rake
 * - Corrections: Manual stock adjustments
 */
final readonly class UpdateStockLedger
{
    private const STOCK_REQUIREMENT_MT_PER_RAKE = 3500;

    /**
     * Record a stock receipt (coal arrival)
     */
    public function recordReceipt(
        VehicleArrival $vehicleArrival,
        float $quantity,
        ?string $remarks = null,
        int $userId = 0
    ): StockLedger {
        return DB::transaction(function () use ($vehicleArrival, $quantity, $remarks, $userId): StockLedger {
            $siding = $vehicleArrival->siding;

            // Get current balance
            $currentBalance = $this->getCurrentBalance($siding->id);
            $newBalance = $currentBalance + $quantity;

            // Create ledger entry
            $ledger = StockLedger::query()->create([
                'siding_id' => $siding->id,
                'transaction_type' => 'receipt',
                'vehicle_arrival_id' => $vehicleArrival->id,
                'quantity_mt' => $quantity,
                'opening_balance_mt' => $currentBalance,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'RCPT-'.$vehicleArrival->id,
                'remarks' => $remarks,
                'created_by' => $userId > 0 ? $userId : null,
            ]);

            // Update CoalStock for this siding
            $this->updateCoalStockBalance($siding->id, $newBalance);

            event(new CoalStockUpdated($siding->id, $newBalance));

            $this->maybeNotifyCapacityIncrease($siding, $currentBalance, $newBalance);

            return $ledger;
        });
    }

    /**
     * Record a stock receipt from a vehicle unload (receipt confirmation)
     */
    public function recordReceiptFromUnload(
        VehicleUnload $vehicleUnload,
        float $quantity,
        ?string $remarks = null,
        int $userId = 0
    ): StockLedger {
        return DB::transaction(function () use ($vehicleUnload, $quantity, $remarks, $userId): StockLedger {
            $siding = $vehicleUnload->siding;
            $currentBalance = $this->getCurrentBalance($siding->id);
            $newBalance = $currentBalance + $quantity;

            $ledger = StockLedger::query()->create([
                'siding_id' => $siding->id,
                'transaction_type' => 'receipt',
                'vehicle_arrival_id' => null,
                'quantity_mt' => $quantity,
                'opening_balance_mt' => $currentBalance,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'UNLOAD-'.$vehicleUnload->id,
                'remarks' => $remarks ?? 'Vehicle unload #'.$vehicleUnload->id,
                'created_by' => $userId > 0 ? $userId : null,
            ]);

            $this->updateCoalStockBalance($siding->id, $newBalance);

            event(new CoalStockUpdated($siding->id, $newBalance));

            $this->maybeNotifyCapacityIncrease($siding, $currentBalance, $newBalance);

            return $ledger;
        });
    }

    /**
     * Record a stock dispatch (coal departure)
     */
    public function recordDispatch(
        Siding $siding,
        float $quantity,
        ?int $rakeId = null,
        ?string $remarks = null,
        int $userId = 0,
        ?int $rakeWeighmentId = null,
    ): StockLedger {
        return DB::transaction(function () use ($siding, $quantity, $rakeId, $remarks, $userId, $rakeWeighmentId): StockLedger {

            $sidingId = (int) $siding->id;

            $lastLedger = StockLedger::query()
                ->where('siding_id', $sidingId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $opening = $lastLedger !== null
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

            //  ALWAYS negative
            $dispatchQty = -abs($quantity);

            $closing = round($opening + $dispatchQty, 2);

            throw_if(
                $closing < 0,
                InvalidArgumentException::class,
                "Insufficient stock. Available: {$opening} MT, Required: {$quantity} MT"
            );

            $ledger = StockLedger::create([
                'siding_id' => $sidingId,
                'transaction_type' => 'dispatch',
                'rake_id' => $rakeId,
                'rake_weighment_id' => $rakeWeighmentId,
                'quantity_mt' => $dispatchQty, //  negative
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'reference_number' => 'DISP-'.($rakeId ?? 'MAN'),
                'remarks' => $remarks,
                'created_by' => $userId ?: null,
            ]);

            DB::afterCommit(function () use ($sidingId, $closing): void {
                event(new CoalStockUpdated($sidingId, $closing));
            });

            return $ledger;
        });
    }

    /**
     * Apply a signed net-weight delta for a rake weighment: positive = more coal dispatched out;
     * negative = net returning to siding (correction row, same pattern as weighment delete reversal).
     */
    public function applyRakeWeighmentNetDelta(
        Siding $siding,
        int $rakeId,
        int $rakeWeighmentId,
        float $deltaMt,
        int $userId,
        ?string $remarks = null,
    ): ?StockLedger {
        $deltaMt = round($deltaMt, 2);

        if (abs($deltaMt) < 0.005) {
            return null;
        }

        if ($deltaMt > 0) {
            return $this->recordDispatch(
                $siding,
                $deltaMt,
                $rakeId,
                $remarks ?? 'Rake weighment net weight increased',
                $userId,
                $rakeWeighmentId,
            );
        }

        return $this->recordRakeWeighmentReturnCorrection(
            $siding,
            $rakeId,
            $rakeWeighmentId,
            abs($deltaMt),
            $userId,
            $remarks ?? 'Rake weighment net weight decreased',
        );
    }

    /**
     * Adds stock back when net weight is reduced (positive quantity_mt, correction), mirroring weighment delete reversal.
     */
    public function recordRakeWeighmentReturnCorrection(
        Siding $siding,
        int $rakeId,
        int $rakeWeighmentId,
        float $quantityMt,
        int $userId,
        ?string $remarks = null,
    ): StockLedger {
        return DB::transaction(function () use ($siding, $rakeId, $rakeWeighmentId, $quantityMt, $userId, $remarks): StockLedger {
            $sidingId = (int) $siding->id;
            $qty = round(abs($quantityMt), 2);

            $lastLedger = StockLedger::query()
                ->where('siding_id', $sidingId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $opening = $lastLedger !== null
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

            $closing = round($opening + $qty, 2);

            $ledger = StockLedger::query()->create([
                'siding_id' => $sidingId,
                'transaction_type' => 'correction',
                'rake_id' => $rakeId,
                'rake_weighment_id' => $rakeWeighmentId,
                'quantity_mt' => $qty,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'reference_number' => 'CORR-RW-'.$rakeWeighmentId.'-'.now()->timestamp,
                'remarks' => $remarks,
                'created_by' => $userId ?: null,
            ]);

            DB::afterCommit(function () use ($sidingId, $closing): void {
                event(new CoalStockUpdated($sidingId, $closing));
            });

            return $ledger;
        });
    }

    /**
     * Reverse the net stock effect when ledger rows show net dispatch out (negative sum of quantity_mt).
     * Targets rows for a rake_weighment_id, or legacy rows with rake_id set and rake_weighment_id null.
     * Idempotent when a reversal row with the computed reference_number already exists.
     */
    public function reverseStockLedgerNetForRakeWeighment(
        Siding $siding,
        int $rakeId,
        ?int $rakeWeighmentId,
        int $userId,
        string $remarks,
    ): void {
        DB::transaction(function () use ($siding, $rakeId, $rakeWeighmentId, $userId, $remarks): void {
            $sidingId = (int) $siding->id;

            $query = StockLedger::query()->where('siding_id', $sidingId)->where('rake_id', $rakeId);

            if ($rakeWeighmentId !== null) {
                $query->where('rake_weighment_id', $rakeWeighmentId);
            } else {
                $query->whereNull('rake_weighment_id');
            }

            $netSum = (float) $query->sum('quantity_mt');

            if ($netSum >= -0.005) {
                return;
            }

            $referenceNumber = $rakeWeighmentId !== null
                ? 'REV-RW-'.$rakeWeighmentId
                : 'REV-RW-LEGACY-R'.$rakeId;

            $alreadyReversed = StockLedger::query()
                ->where('reference_number', $referenceNumber)
                ->lockForUpdate()
                ->exists();

            if ($alreadyReversed) {
                return;
            }

            $reverseQty = abs($netSum);

            $lastLedger = StockLedger::query()
                ->where('siding_id', $sidingId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $opening = $lastLedger !== null
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

            $closing = round($opening + $reverseQty, 2);

            StockLedger::query()->create([
                'siding_id' => $sidingId,
                'transaction_type' => 'correction',
                'rake_id' => $rakeId,
                'rake_weighment_id' => $rakeWeighmentId,
                'quantity_mt' => $reverseQty,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'reference_number' => $referenceNumber,
                'remarks' => $remarks,
                'created_by' => $userId ?: null,
            ]);

            DB::afterCommit(function () use ($sidingId, $closing): void {
                event(new CoalStockUpdated($sidingId, $closing));
            });
        });
    }

    /**
     * Record a stock correction (manual adjustment)
     */
    public function recordCorrection(
        Siding $siding,
        float $correctionQuantity,
        string $reason,
        int $userId = 0
    ): StockLedger {
        return DB::transaction(function () use ($siding, $correctionQuantity, $reason, $userId): StockLedger {
            $currentBalance = $this->getCurrentBalance($siding->id);
            $newBalance = $currentBalance - abs($correctionQuantity);

            // Create ledger entry
            $ledger = StockLedger::query()->create([
                'siding_id' => $siding->id,
                'transaction_type' => 'correction',
                'quantity_mt' => abs($correctionQuantity),
                'opening_balance_mt' => $currentBalance,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'CORR-'.now()->timestamp,
                'remarks' => $reason,
                'created_by' => $userId > 0 ? $userId : null,
            ]);

            // Update CoalStock
            $this->updateCoalStockBalance($siding->id, $newBalance);

            event(new CoalStockUpdated($siding->id, $newBalance));

            $this->maybeNotifyCapacityIncrease($siding, $currentBalance, $newBalance);

            return $ledger;
        });
    }

    /**
     * Get current stock balance for a siding
     */
    public function getCurrentBalance(int $sidingId): float
    {
        $lastLedger = StockLedger::query()->where('siding_id', $sidingId)
            ->latest('created_at')
            ->first();

        if ($lastLedger !== null) {
            return (float) $lastLedger->closing_balance_mt;
        }

        return SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);
    }

    /**
     * Get stock summary for a siding
     */
    public function getStockSummary(int $sidingId): array
    {
        $receipts = StockLedger::query()->where('siding_id', $sidingId)
            ->receipts()
            ->recent()
            ->sum('quantity_mt');

        $dispatches = StockLedger::query()->where('siding_id', $sidingId)
            ->dispatches()
            ->recent()
            ->sum('quantity_mt');

        $currentBalance = $this->getCurrentBalance($sidingId);

        return [
            'current_balance_mt' => $currentBalance,
            'receipts_30_days' => $receipts,
            'dispatches_30_days' => $dispatches,
            'net_change_30_days' => $receipts - $dispatches,
            'last_updated' => StockLedger::query()->where('siding_id', $sidingId)
                ->latest('created_at')
                ->value('created_at'),
        ];
    }

    /**
     * Get ledger history for a siding
     */
    public function getLedgerHistory(int $sidingId, int $limit = 50): \Illuminate\Pagination\Paginate
    {
        return StockLedger::query()->where('siding_id', $sidingId)
            ->with('vehicleArrival.vehicle', 'rake', 'creator')
            ->latest('created_at')
            ->paginate($limit);
    }

    private function maybeNotifyCapacityIncrease(Siding $siding, float $oldBalance, float $newBalance): void
    {
        $req = self::STOCK_REQUIREMENT_MT_PER_RAKE;
        $oldCapacity = $req > 0 ? (int) floor($oldBalance / $req) : 0;
        $newCapacity = $req > 0 ? (int) floor($newBalance / $req) : 0;

        if ($newCapacity <= 0 || $newCapacity <= $oldCapacity) {
            return;
        }

        DB::afterCommit(function () use ($siding, $newBalance, $newCapacity, $req): void {
            NotifySuperAdmins::dispatch(\App\Notifications\StockCapacityIncreasedNotification::class, [
                'siding_id' => (int) $siding->id,
                'siding_name' => $siding->name,
                'closing_balance_mt' => round($newBalance, 2),
                'capacity_rakes' => $newCapacity,
                'requirement_mt' => $req,
            ]);
        });
    }

    /**
     * Update CoalStock table with current balance
     */
    private function updateCoalStockBalance(int $sidingId, float $balance): void
    {
        $today = now()->toDateString();

        // Upsert today's coal stock record
        \App\Models\CoalStock::query()->updateOrCreate([
            'siding_id' => $sidingId,
            'as_of_date' => $today,
        ], [
            'closing_balance_mt' => $balance,
        ]);
    }
}
