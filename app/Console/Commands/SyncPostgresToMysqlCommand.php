<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync changed rows from PostgreSQL back to legacy MySQL (incremental, every 30 min).
 *
 * Conflict resolution: last-write-wins by updated_at. If PostgreSQL row has a newer
 * updated_at than the MySQL row, PostgreSQL data overwrites MySQL.
 *
 * Opt-in: scheduler entry is registered but disabled (SYNC_COEXISTENCE_ENABLED env var).
 * Run manually for testing.
 */
final class SyncPostgresToMysqlCommand extends Command
{
    protected $signature = 'fusion:sync-postgres-to-mysql
                            {--dry-run : Preview without writing to MySQL}
                            {--chunk=500 : Rows per chunk}
                            {--since= : Override last-sync timestamp (ISO8601)}
                            {--tables=all : Comma-separated table names to sync, or "all"}';

    protected $description = 'Incremental sync: push changed rows from PostgreSQL into legacy MySQL (last-write-wins by updated_at).';

    /** @var array<string, array{source: string, target: string, legacy_key: string, mysql_table: string}> */
    private array $tableMap = [
        'contacts' => [
            'source' => 'contacts',
            'target' => 'leads',
            'legacy_key' => 'legacy_lead_id',
            'mysql_table' => 'leads',
        ],
        'projects' => [
            'source' => 'projects',
            'target' => 'projects',
            'legacy_key' => 'legacy_id',
            'mysql_table' => 'projects',
        ],
        'lots' => [
            'source' => 'lots',
            'target' => 'lots',
            'legacy_key' => 'legacy_id',
            'mysql_table' => 'lots',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $sinceOverride = $this->option('since');
        $tablesOption = $this->option('tables');

        $enabledTables = $tablesOption === 'all'
            ? array_keys($this->tableMap)
            : array_map('trim', explode(',', (string) $tablesOption));

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
            Log::info('fusion:sync-postgres-to-mysql dry-run started', ['tables' => $enabledTables]);
        } else {
            $this->info($this->description);
            Log::info('fusion:sync-postgres-to-mysql started', ['tables' => $enabledTables]);
        }

        $overallFailed = 0;

        foreach ($enabledTables as $tableKey) {
            if (! isset($this->tableMap[$tableKey])) {
                $this->warn("Unknown table key: {$tableKey} — skipping.");

                continue;
            }

            $overallFailed += $this->syncTable(
                tableKey: $tableKey,
                dryRun: $dryRun,
                chunk: $chunk,
                sinceOverride: $sinceOverride,
            );
        }

        $status = $overallFailed === 0 ? 'completed' : 'completed_with_errors';
        Log::info("fusion:sync-postgres-to-mysql {$status}", ['failed_rows' => $overallFailed]);

        if ($dryRun) {
            $this->info('[DRY RUN] Dry-run complete. No data written. Check logs for details.');
        }

        return $overallFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function syncTable(string $tableKey, bool $dryRun, int $chunk, ?string $sinceOverride): int
    {
        $cfg = $this->tableMap[$tableKey];
        $pgTable = $cfg['source'];
        $mysqlTable = $cfg['mysql_table'];
        $legacyKey = $cfg['legacy_key'];
        $direction = 'pgsql_to_mysql';

        // Determine last sync timestamp
        $lastSyncAt = $sinceOverride;

        if ($lastSyncAt === null) {
            $state = DB::table('sync_state')
                ->where('direction', $direction)
                ->where('table_name', $tableKey)
                ->first();

            $lastSyncAt = $state?->last_synced_at;
        }

        $this->info("Syncing [{$tableKey}] PG:{$pgTable} → MySQL:{$mysqlTable} (since: ".($lastSyncAt ?? 'beginning').')');

        // Verify legacy DB is reachable
        try {
            DB::connection('mysql_legacy')->getPdo();
        } catch (Throwable $e) {
            $this->warn("  [{$tableKey}] Cannot connect to legacy DB: {$e->getMessage()}");

            return 0;
        }

        $query = DB::table($pgTable)->orderBy('id');

        if ($lastSyncAt !== null) {
            $query->where('updated_at', '>', $lastSyncAt);
        }

        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $newLastSyncAt = null;

        $this->info("  [{$tableKey}] {$total} rows to sync.");

        if ($total === 0) {
            $this->updateSyncState($direction, $tableKey, now()->toDateTimeString(), $dryRun);

            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunk, function ($rows) use (
            $pgTable, $mysqlTable, $legacyKey, $tableKey, $dryRun, $direction,
            &$processed, &$skipped, &$failed, &$newLastSyncAt, $bar
        ): void {
            try {
                foreach ($rows as $pgRow) {
                    $row = (array) $pgRow;
                    $pgUpdatedAt = $row['updated_at'] ?? null;
                    $legacyId = $row[$legacyKey] ?? null;

                    try {
                        // For rows without a legacy_id, they are PG-native (created in PG after cutover).
                        // We skip creating them in MySQL unless the legacy app needs them.
                        if ($legacyId === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        // Find existing MySQL row
                        $mysqlRow = DB::connection('mysql_legacy')
                            ->table($mysqlTable)
                            ->where('id', $legacyId)
                            ->first();

                        // Conflict resolution: last-write-wins by updated_at
                        $mysqlUpdatedAt = $mysqlRow?->updated_at ?? null;

                        if ($mysqlRow && $mysqlUpdatedAt && $pgUpdatedAt) {
                            if (strtotime((string) $mysqlUpdatedAt) > strtotime((string) $pgUpdatedAt)) {
                                // MySQL row is newer — skip (MySQL wins; mysql-to-pgsql sync will handle it)
                                $skipped++;
                                $bar->advance();

                                continue;
                            }
                        }

                        $mapped = $this->mapPostgresRowToMysql($pgTable, $row);

                        if ($mapped === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        if (! $dryRun) {
                            DB::connection('mysql_legacy')
                                ->table($mysqlTable)
                                ->where('id', $legacyId)
                                ->update($mapped);

                            $this->writeSyncLog($direction, $mysqlTable, (string) $legacyId, 'success', null);
                        }

                        if ($pgUpdatedAt && ($newLastSyncAt === null || strtotime((string) $pgUpdatedAt) > strtotime((string) $newLastSyncAt))) {
                            $newLastSyncAt = $pgUpdatedAt;
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        $msg = "fusion:sync-postgres-to-mysql [{$tableKey}] row ".($legacyId ?? '?').": {$e->getMessage()}";
                        Log::warning($msg);
                        if (! $dryRun) {
                            $this->writeSyncLog($direction, $mysqlTable, (string) ($legacyId ?? '?'), 'failed', $e->getMessage());
                        }
                    }

                    $bar->advance();
                }
            } catch (Throwable $e) {
                $this->error("Chunk failed: {$e->getMessage()}");
                $failed += count($rows);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("  [{$tableKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        if (! $dryRun) {
            $this->updateSyncState($direction, $tableKey, $newLastSyncAt ?? now()->toDateTimeString(), false);
        }

        return $failed;
    }

    /**
     * Map a PostgreSQL row back to the MySQL (legacy) schema for the given target.
     * Returns null to skip the row.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapPostgresRowToMysql(string $pgTable, array $row): ?array
    {
        return match ($pgTable) {
            'contacts' => $this->mapContactToMysql($row),
            'projects' => $this->mapProjectToMysql($row),
            'lots' => $this->mapLotToMysql($row),
            default => null,
        };
    }

    /** @param array<string, mixed> $row */
    private function mapContactToMysql(array $row): array
    {
        return [
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'job_title' => $row['job_title'] ?? null,
            'stage' => $row['stage'] ?? null,
            'company' => $row['company_name'] ?? null,
            'last_followup_at' => $row['last_followup_at'] ?? null,
            'next_followup_at' => $row['next_followup_at'] ?? null,
            'last_contacted_at' => $row['last_contacted_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function mapProjectToMysql(array $row): array
    {
        return [
            'name' => $row['name'] ?? null,
            'slug' => $row['slug'] ?? null,
            'status' => $row['status'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function mapLotToMysql(array $row): array
    {
        return [
            'lot_number' => $row['lot_number'] ?? null,
            'title' => $row['title'] ?? null,
            'status' => $row['status'] ?? null,
            'price' => $row['price'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    private function updateSyncState(string $direction, string $tableName, string $lastSyncedAt, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        DB::table('sync_state')->updateOrInsert(
            ['direction' => $direction, 'table_name' => $tableName],
            ['last_synced_at' => $lastSyncedAt, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function writeSyncLog(string $direction, string $tableName, string $rowKey, string $status, ?string $message): void
    {
        DB::table('sync_log')->insert([
            'direction' => $direction,
            'table_name' => $tableName,
            'row_key' => $rowKey,
            'status' => $status,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
