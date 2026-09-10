<?php

declare(strict_types=1);

namespace App\Support\Rollups;

/**
 * PostgreSQL SQL snippets for aggregates sourced from {@see \App\Models\DailyVehicleEntry}.
 *
 * Calendar grain matches road-dispatch screens: {@see date entry_date} as {@code ::date}.
 */
final readonly class DailyVehicleEntryRollupSql
{
    public static function rollupDayExpression(string $qualifiedColumn = 'daily_vehicle_entries.entry_date'): string
    {
        return "({$qualifiedColumn})::date";
    }

    public static function completedEntriesCountExpression(): string
    {
        return "SUM(CASE WHEN daily_vehicle_entries.status = 'completed' THEN 1 ELSE 0 END)";
    }

    public static function pendingEntriesCountExpression(): string
    {
        return "SUM(CASE WHEN daily_vehicle_entries.status <> 'completed' THEN 1 ELSE 0 END)";
    }

    public static function roundedSumCompletedNetWt(): string
    {
        return 'ROUND(CAST(SUM(CASE WHEN daily_vehicle_entries.status = \'completed\' THEN COALESCE(daily_vehicle_entries.net_wt, 0) ELSE 0 END) AS NUMERIC), 2)';
    }

    public static function roundedSumPendingGrossWt(): string
    {
        return 'ROUND(CAST(SUM(CASE WHEN daily_vehicle_entries.status <> \'completed\' THEN COALESCE(daily_vehicle_entries.gross_wt, 0) ELSE 0 END) AS NUMERIC), 2)';
    }
}
