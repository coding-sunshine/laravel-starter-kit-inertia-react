<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Daily rollup sync (rollup:sync-daily)
    |--------------------------------------------------------------------------
    |
    | When local clock hour is strictly less than this value (0–23), the sync
    | also rebuilds the previous calendar day so late arrivals still aggregate
    | into yesterday's rollup rows.
    |
    */
    'daily_sync' => [
        'early_refresh_until_hour_exclusive' => (int) env('ROLLUPS_DAILY_SYNC_EARLY_UNTIL_HOUR', 6),
    ],
];
