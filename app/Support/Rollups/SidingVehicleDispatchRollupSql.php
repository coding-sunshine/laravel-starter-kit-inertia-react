<?php

declare(strict_types=1);

namespace App\Support\Rollups;

/**
 * PostgreSQL SQL snippets for aggregates sourced from {@see \App\Models\SidingVehicleDispatch}.
 *
 * Calendar extraction on {@see timestamp issued_on} uses a naive {@code ::date} cast so rollup buckets match the Vehicle Dispatch register
 * ({@see \App\Models\VehicleDispatch::ISSUED_ON_DATE_SQL} and Laravel {@code whereDate('issued_on', …)} on PostgreSQL).
 */
final readonly class SidingVehicleDispatchRollupSql
{
    /**
     * Calendar date expression for grouping/filtering {@see timestamp issued_on} (same semantics as {@see \App\Models\VehicleDispatch::ISSUED_ON_DATE_SQL}).
     */
    public static function issuedOnCalendarDateExpression(string $qualifiedColumn = 'siding_vehicle_dispatches.issued_on'): string
    {
        return "({$qualifiedColumn})::date";
    }

    /**
     * Shift ordinal labels ({@code 1st}/{@code 2nd}/{@code 3rd}) → {@code 1}/{@code 2}/{@code 3}; blank/null → {@code 0}.
     */
    public static function shiftNumberExpression(string $qualifiedColumn = 'siding_vehicle_dispatches.shift'): string
    {
        return <<<SQL
CASE
    WHEN LENGTH(TRIM(COALESCE({$qualifiedColumn}::text, ''))) < 1 THEN 0
    ELSE CAST(SUBSTRING(TRIM(COALESCE({$qualifiedColumn}::text, '')), 1, 1) AS INTEGER)
END
SQL;
    }

    /**
     * Rounded sum of mineral weights for rollup totals.
     */
    public static function roundedSumMineralWeight(string $qualifiedColumn = 'siding_vehicle_dispatches.mineral_weight'): string
    {
        return "ROUND(CAST(SUM(CAST({$qualifiedColumn} AS DECIMAL(24, 8))) AS NUMERIC), 2)";
    }
}
