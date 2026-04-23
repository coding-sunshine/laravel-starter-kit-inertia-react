<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\StockLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Read-only daily opening/closing balances derived from {@see StockLedger} rows.
 */
final class StockLedgerDailyBalanceReport
{
    public const string QUIET_DAY_REMARKS = 'No ledger transactions on this date.';

    /**
     * @return array{
     *     siding: array{id: int, name: string, code: string},
     *     from: string,
     *     to: string,
     *     days: list<array{date: string, opening_mt: float, closing_mt: float, remarks: string|null}>
     * }
     */
    public static function build(Siding $siding, string $from, string $to): array
    {
        $fromDate = CarbonImmutable::parse($from)->startOfDay();
        $toDate = CarbonImmutable::parse($to)->startOfDay();
        $today = CarbonImmutable::now()->startOfDay();

        if ($toDate->gt($today)) {
            $toDate = $today;
        }

        if ($fromDate->gt($toDate)) {
            return [
                'siding' => [
                    'id' => (int) $siding->id,
                    'name' => (string) $siding->name,
                    'code' => (string) $siding->code,
                ],
                'from' => $fromDate->toDateString(),
                'to' => $toDate->toDateString(),
                'days' => [],
            ];
        }

        $sidingId = (int) $siding->id;

        $lastBefore = StockLedger::query()
            ->where('siding_id', $sidingId)
            ->whereDate('created_at', '<', $fromDate->toDateString())
            ->latest('id')
            ->first();

        $running = $lastBefore !== null
            ? (float) $lastBefore->closing_balance_mt
            : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

        /** @var Collection<string, Collection<int, StockLedger>> $byDate */
        $byDate = StockLedger::query()
            ->where('siding_id', $sidingId)
            ->whereDate('created_at', '>=', $fromDate->toDateString())
            ->whereDate('created_at', '<=', $toDate->toDateString())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at', 'opening_balance_mt', 'closing_balance_mt'])
            ->groupBy(fn (StockLedger $row): string => $row->created_at->toDateString());

        $daysAsc = [];

        for ($cursor = $fromDate; $cursor->lte($toDate); $cursor = $cursor->addDay()) {
            $dateStr = $cursor->toDateString();
            $dayRows = $byDate->get($dateStr);

            if ($dayRows === null || $dayRows->isEmpty()) {
                $daysAsc[] = [
                    'date' => $dateStr,
                    'opening_mt' => round($running, 2),
                    'closing_mt' => round($running, 2),
                    'remarks' => self::QUIET_DAY_REMARKS,
                ];

                continue;
            }

            $first = $dayRows->first();
            $last = $dayRows->last();
            $open = (float) $first->opening_balance_mt;
            $close = (float) $last->closing_balance_mt;

            $daysAsc[] = [
                'date' => $dateStr,
                'opening_mt' => round($open, 2),
                'closing_mt' => round($close, 2),
                'remarks' => null,
            ];

            $running = $close;
        }

        return [
            'siding' => [
                'id' => $sidingId,
                'name' => (string) $siding->name,
                'code' => (string) $siding->code,
            ],
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'days' => array_reverse($daysAsc),
        ];
    }
}
