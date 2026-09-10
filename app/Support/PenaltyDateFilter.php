<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Shared `filter[penalty_date]` handling for the penalty register, charts, and
 * analytics so all three read the same computed date the same way.
 *
 * The date expression coalesces RR receipt date, then rake loading date, then
 * snapshot creation. The underlying columns are timestamps (rr_received_date is
 * a dateTime), so filters operate at day granularity: a plain BETWEEN against
 * 'Y-m-d' strings would drop rows on the upper-bound day. Postgres disallows
 * referencing a SELECT-list alias in WHERE, so the COALESCE expression is
 * repeated via whereRaw rather than filtering on an alias.
 */
final class PenaltyDateFilter
{
    public const DATE_EXPR = 'COALESCE(rr_documents.rr_received_date, rakes.loading_date, rr_penalty_snapshots.created_at)';

    public static function apply(Builder|EloquentBuilder $query, mixed $value): void
    {
        $raw = is_array($value) ? implode(',', $value) : (string) $value;
        $operator = 'eq';
        $rawValue = $raw;

        if (preg_match('/^([a-z_]+):(.+)$/i', $raw, $matches) && in_array($matches[1], ['eq', 'gte', 'lte', 'between', 'before', 'after'], true)) {
            $operator = $matches[1];
            $rawValue = $matches[2];
        }

        $values = explode(',', $rawValue);
        $expr = self::DATE_EXPR;
        $startOfDay = fn (string $v): string => Carbon::parse($v)->startOfDay()->toDateTimeString();
        $startOfNextDay = fn (string $v): string => Carbon::parse($v)->startOfDay()->addDay()->toDateTimeString();

        match ($operator) {
            'between' => count($values) >= 2
                ? $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [$startOfDay($values[0]), $startOfNextDay($values[1])])
                : $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [$startOfDay($values[0]), $startOfNextDay($values[0])]),
            'before' => $query->whereRaw("{$expr} < ?", [$startOfDay($values[0])]),
            'after' => $query->whereRaw("{$expr} >= ?", [$startOfNextDay($values[0])]),
            'gte' => $query->whereRaw("{$expr} >= ?", [$startOfDay($values[0])]),
            'lte' => $query->whereRaw("{$expr} < ?", [$startOfNextDay($values[0])]),
            default => $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [$startOfDay($values[0]), $startOfNextDay($values[0])]),
        };
    }
}
