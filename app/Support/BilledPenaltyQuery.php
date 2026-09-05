<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\RrPenaltySnapshot;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Single source for "penalties actually billed on an RR".
 *
 * The legacy `penalties` table is empty in production; every billed penalty
 * lives in `rr_penalty_snapshots` (`applied_penalties` holds the predicted
 * side). Analytics, the AI briefs and the penalty register all read through
 * here so they agree on the joins and on which date a penalty belongs to.
 *
 * Fields the legacy table carried but snapshots do not — dispute state,
 * responsible party, root cause — have no substitute and are not emulated.
 */
final class BilledPenaltyQuery
{
    /**
     * @param  array<int>  $sidingIds
     * @return Builder<RrPenaltySnapshot>
     */
    public static function base(array $sidingIds): Builder
    {
        return RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
            ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code')
            ->whereIn('rakes.siding_id', $sidingIds);
    }

    /**
     * @param  array<int>  $sidingIds
     * @return Builder<RrPenaltySnapshot>
     */
    public static function between(array $sidingIds, CarbonInterface $from, ?CarbonInterface $to = null): Builder
    {
        $expr = PenaltyDateFilter::DATE_EXPR;
        $to ??= Carbon::now();

        return self::base($sidingIds)->whereRaw(
            "{$expr} >= ? AND {$expr} < ?",
            [$from->copy()->startOfDay()->toDateTimeString(), $to->copy()->startOfDay()->addDay()->toDateTimeString()]
        );
    }

    /**
     * @param  array<int>  $sidingIds
     * @return Builder<RrPenaltySnapshot>
     */
    public static function forMonth(array $sidingIds, CarbonInterface $month): Builder
    {
        return self::between($sidingIds, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());
    }

    /**
     * Penalty type name, incident count and billed total, biggest first.
     *
     * @param  Builder<RrPenaltySnapshot>  $query
     * @return list<array{type: string, count: int, total: float}>
     */
    public static function totalsByType(Builder $query): array
    {
        return $query
            ->selectRaw('COALESCE(penalty_types.name, rr_penalty_snapshots.penalty_code) as type, count(*) as cnt, sum(rr_penalty_snapshots.amount) as total')
            ->groupBy('penalty_types.name', 'rr_penalty_snapshots.penalty_code')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'type' => (string) $row->type,
                'count' => (int) $row->cnt,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    /**
     * @param  Builder<RrPenaltySnapshot>  $query
     * @return list<array{siding: string, count: int, total: float, average: float}>
     */
    public static function totalsBySiding(Builder $query): array
    {
        return $query
            ->selectRaw('sidings.name as siding, count(*) as cnt, sum(rr_penalty_snapshots.amount) as total, avg(rr_penalty_snapshots.amount) as avg_amount')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'siding' => (string) $row->siding,
                'count' => (int) $row->cnt,
                'total' => (float) $row->total,
                'average' => round((float) $row->avg_amount, 2),
            ])
            ->all();
    }

    /**
     * @param  Builder<RrPenaltySnapshot>  $query
     * @return list<array{siding: string, type: string, count: int, total: float}>
     */
    public static function totalsBySidingAndType(Builder $query): array
    {
        return $query
            ->selectRaw('sidings.name as siding, COALESCE(penalty_types.name, rr_penalty_snapshots.penalty_code) as type, count(*) as cnt, sum(rr_penalty_snapshots.amount) as total')
            ->groupBy('sidings.name', 'penalty_types.name', 'rr_penalty_snapshots.penalty_code')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'siding' => (string) $row->siding,
                'type' => (string) $row->type,
                'count' => (int) $row->cnt,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    /**
     * One row per billed penalty with its effective date, for trends that group
     * by day or weekday. The date expression differs per driver, so grouping is
     * done in PHP by the caller rather than in SQL.
     *
     * @param  Builder<RrPenaltySnapshot>  $query
     * @return Collection<int, object>
     */
    public static function dated(Builder $query): Collection
    {
        $expr = PenaltyDateFilter::DATE_EXPR;

        return $query
            ->selectRaw("{$expr} as penalty_date, rr_penalty_snapshots.amount as amount, COALESCE(penalty_types.name, rr_penalty_snapshots.penalty_code) as type, sidings.name as siding")
            ->toBase()
            ->get();
    }

    /**
     * @param  Builder<RrPenaltySnapshot>  $query
     */
    public static function total(Builder $query): float
    {
        return (float) $query->sum('rr_penalty_snapshots.amount');
    }
}
