<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\CoalStockUpdated;
use App\Http\Requests\AdjustStockLedgerRequest;
use App\Models\CoalStock;
use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

/**
 * Stock ledger browser and **UI-only** manual adjustments. Adjustment logic lives here
 * intentionally separate from {@see \App\Actions\UpdateStockLedger} domain flows.
 */
final class StockLedgerController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $filters = $request->validate([
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'rake_number' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'transaction_type' => ['nullable', 'string', 'in:receipt,dispatch,correction'],
        ]);

        $query = StockLedger::query()
            ->with([
                'siding:id,name',
                'rake:id,rake_number',
                'creator:id,name,email',
            ])
            ->latest('id');

        if (! empty($filters['siding_id'])) {
            $query->where('siding_id', $filters['siding_id']);
        }

        $rakeNumber = isset($filters['rake_number']) ? mb_trim((string) $filters['rake_number']) : '';
        if ($rakeNumber !== '') {
            $needle = $rakeNumber;
            $query->whereHas('rake', static function ($q) use ($needle): void {
                $q->whereRaw('LOWER(rake_number) = LOWER(?)', [$needle]);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        $ledgers = $query->paginate(30)->withQueryString();

        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/StockLedger/Index', [
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'ledgers' => [
                'data' => $ledgers->getCollection()->map(fn (StockLedger $row): array => $this->ledgerRow($row))->values()->all(),
                'current_page' => $ledgers->currentPage(),
                'last_page' => $ledgers->lastPage(),
                'per_page' => $ledgers->perPage(),
                'total' => $ledgers->total(),
                'links' => $ledgers->linkCollection()->map(static fn ($link): array => [
                    'url' => data_get($link, 'url'),
                    'label' => (string) data_get($link, 'label', ''),
                    'active' => (bool) data_get($link, 'active', false),
                ])->values()->all(),
            ],
            'sidings' => $sidings,
            'filters' => [
                'siding_id' => $filters['siding_id'] ?? null,
                'rake_number' => $rakeNumber !== '' ? $rakeNumber : null,
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'transaction_type' => $filters['transaction_type'] ?? null,
            ],
        ]);
    }

    public function adjust(AdjustStockLedgerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $siding = Siding::query()->findOrFail((int) $data['siding_id']);
        $userId = (int) $request->user()->id;
        $qty = (float) $data['quantity_mt'];
        $remarks = (string) $data['remarks'];

        try {
            if ($data['direction'] === 'add') {
                $this->applyUiManualReceipt($siding, $qty, $remarks, $userId);
            } else {
                $this->applyUiManualDeduction($siding, $qty, $remarks, $userId);
            }
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['quantity_mt' => $e->getMessage()]);
        }

        return redirect()
            ->back()
            ->with('success', 'Stock adjustment recorded.');
    }

    /**
     * UI-only manual increase (receipt row). Not used by rake/road domain actions.
     */
    private function applyUiManualReceipt(Siding $siding, float $quantity, string $remarks, int $userId): void
    {
        DB::transaction(function () use ($siding, $quantity, $remarks, $userId): void {
            $quantity = round(abs($quantity), 2);
            throw_if(
                $quantity < 0.005,
                InvalidArgumentException::class,
                'Quantity must be greater than zero.'
            );

            $sidingId = (int) $siding->id;
            [$opening, $newBalance] = $this->lockedBalancesAfterDelta($sidingId, $quantity);

            StockLedger::query()->create([
                'siding_id' => $sidingId,
                'transaction_type' => 'receipt',
                'vehicle_arrival_id' => null,
                'quantity_mt' => $quantity,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'UI-RCP-'.uniqid('', true),
                'remarks' => $remarks,
                'created_by' => $userId,
            ]);

            $this->syncCoalStockSnapshot($sidingId, $newBalance);
            event(new CoalStockUpdated($sidingId, $newBalance));
        });
    }

    /**
     * UI-only manual decrease (correction row). Not used by rake/road domain actions.
     */
    private function applyUiManualDeduction(Siding $siding, float $quantity, string $remarks, int $userId): void
    {
        DB::transaction(function () use ($siding, $quantity, $remarks, $userId): void {
            $deduct = round(abs($quantity), 2);
            throw_if(
                $deduct < 0.005,
                InvalidArgumentException::class,
                'Quantity must be greater than zero.'
            );

            $sidingId = (int) $siding->id;

            $lastLedger = StockLedger::query()
                ->where('siding_id', $sidingId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $opening = $lastLedger !== null
                ? (float) $lastLedger->closing_balance_mt
                : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

            $newBalance = round($opening - $deduct, 2);

            throw_if(
                $newBalance < -0.005,
                InvalidArgumentException::class,
                "Insufficient stock. Available: {$opening} MT, required deduction: {$deduct} MT."
            );

            StockLedger::query()->create([
                'siding_id' => $sidingId,
                'transaction_type' => 'correction',
                'quantity_mt' => $deduct,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'UI-CORR-'.now()->timestamp,
                'remarks' => $remarks,
                'created_by' => $userId,
            ]);

            $this->syncCoalStockSnapshot($sidingId, $newBalance);
            event(new CoalStockUpdated($sidingId, $newBalance));
        });
    }

    /**
     * @return array{0: float, 1: float} Opening balance and new closing after a positive delta (receipt).
     */
    private function lockedBalancesAfterDelta(int $sidingId, float $positiveDelta): array
    {
        $lastLedger = StockLedger::query()
            ->where('siding_id', $sidingId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $opening = $lastLedger !== null
            ? (float) $lastLedger->closing_balance_mt
            : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

        $closing = round($opening + $positiveDelta, 2);

        return [$opening, $closing];
    }

    private function syncCoalStockSnapshot(int $sidingId, float $balance): void
    {
        CoalStock::query()->updateOrCreate(
            [
                'siding_id' => $sidingId,
                'as_of_date' => now()->toDateString(),
            ],
            [
                'closing_balance_mt' => $balance,
            ],
        );
    }

    private function ledgerRow(StockLedger $row): array
    {
        return [
            'id' => $row->id,
            'created_at' => $row->created_at?->toIso8601String(),
            'transaction_type' => $row->transaction_type,
            'quantity_mt' => (float) $row->quantity_mt,
            'opening_balance_mt' => (float) $row->opening_balance_mt,
            'closing_balance_mt' => (float) $row->closing_balance_mt,
            'reference_number' => $row->reference_number,
            'remarks' => $row->remarks,
            'siding' => $row->siding ? [
                'id' => $row->siding->id,
                'name' => $row->siding->name,
            ] : null,
            'rake' => $row->rake ? [
                'id' => $row->rake->id,
                'rake_number' => $row->rake->rake_number,
            ] : null,
            'creator' => $row->creator ? [
                'id' => $row->creator->id,
                'name' => $row->creator->name,
                'email' => $row->creator->email,
            ] : null,
        ];
    }
}
