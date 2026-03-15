<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync changed rows from legacy MySQL to PostgreSQL (incremental, every 30 min).
 *
 * Conflict resolution: last-write-wins by updated_at. If MySQL row has a newer
 * updated_at than the PostgreSQL row, MySQL data overwrites PostgreSQL.
 *
 * Opt-in: scheduler entry is registered but disabled (withoutOverlapping guard +
 * SYNC_COEXISTENCE_ENABLED env var). Run manually for testing.
 */
final class SyncMysqlToPostgresCommand extends Command
{
    protected $signature = 'fusion:sync-mysql-to-postgres
                            {--dry-run : Preview without writing to PostgreSQL}
                            {--chunk=500 : Rows per chunk}
                            {--since= : Override last-sync timestamp (ISO8601)}
                            {--tables=all : Comma-separated table names to sync, or "all"}';

    protected $description = 'Incremental sync: pull changed rows from legacy MySQL into PostgreSQL (last-write-wins by updated_at).';

    /** @var array<string, array{source: string, target: string, key: string}> */
    private array $tableMap = [
        'contacts' => ['source' => 'leads', 'target' => 'contacts', 'key' => 'legacy_lead_id'],
        'projects' => ['source' => 'projects', 'target' => 'projects', 'key' => 'legacy_id'],
        'lots' => ['source' => 'lots', 'target' => 'lots', 'key' => 'legacy_id'],
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
            Log::info('fusion:sync-mysql-to-postgres dry-run started', ['tables' => $enabledTables]);
        } else {
            $this->info($this->description);
            Log::info('fusion:sync-mysql-to-postgres started', ['tables' => $enabledTables]);
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
        Log::info("fusion:sync-mysql-to-postgres {$status}", ['failed_rows' => $overallFailed]);

        if ($dryRun) {
            $this->info('[DRY RUN] Dry-run complete. No data written. Check logs for details.');
        }

        return $overallFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function syncTable(string $tableKey, bool $dryRun, int $chunk, ?string $sinceOverride): int
    {
        $cfg = $this->tableMap[$tableKey];
        $source = $cfg['source'];
        $target = $cfg['target'];
        $legacyKey = $cfg['key'];
        $direction = 'mysql_to_pgsql';

        // Determine last sync timestamp
        $lastSyncAt = $sinceOverride;

        if ($lastSyncAt === null) {
            $state = DB::table('sync_state')
                ->where('direction', $direction)
                ->where('table_name', $tableKey)
                ->first();

            $lastSyncAt = $state?->last_synced_at;
        }

        $this->info("Syncing [{$tableKey}] MySQL:{$source} → PG:{$target} (since: ".($lastSyncAt ?? 'beginning').')');

        try {
            $query = DB::connection('mysql_legacy')->table($source)->orderBy('id');
        } catch (Throwable $e) {
            $this->warn("  [{$tableKey}] Cannot connect to legacy DB: {$e->getMessage()}");

            return 0;
        }

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
            $target, $legacyKey, $tableKey, $dryRun, $direction,
            &$processed, &$skipped, &$failed, &$newLastSyncAt, $bar
        ): void {
            DB::beginTransaction();
            try {
                foreach ($rows as $mysqlRow) {
                    $row = (array) $mysqlRow;
                    $legacyId = $row['id'] ?? null;

                    if ($legacyId === null) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Find existing PG row by legacy key
                    $existing = DB::table($target)
                        ->where($legacyKey, $legacyId)
                        ->first();

                    // Conflict resolution: last-write-wins by updated_at
                    $mysqlUpdatedAt = $row['updated_at'] ?? null;
                    $pgUpdatedAt = $existing?->updated_at ?? null;

                    if ($existing && $pgUpdatedAt && $mysqlUpdatedAt) {
                        if (strtotime((string) $pgUpdatedAt) >= strtotime((string) $mysqlUpdatedAt)) {
                            // PG row is same age or newer — skip (PG wins ties)
                            $skipped++;
                            $bar->advance();

                            continue;
                        }
                    }

                    try {
                        $mapped = $this->mapMysqlRowToPostgres($target, $row);

                        if ($mapped === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        if (! $dryRun) {
                            DB::table($target)->updateOrInsert(
                                [$legacyKey => $legacyId],
                                $mapped
                            );

                            $this->writeSyncLog($direction, $target, (string) $legacyId, 'success', null);
                        }

                        if ($mysqlUpdatedAt && ($newLastSyncAt === null || strtotime((string) $mysqlUpdatedAt) > strtotime((string) $newLastSyncAt))) {
                            $newLastSyncAt = $mysqlUpdatedAt;
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        $msg = "fusion:sync-mysql-to-postgres [{$tableKey}] row {$legacyId}: {$e->getMessage()}";
                        Log::warning($msg);
                        if (! $dryRun) {
                            $this->writeSyncLog($direction, $target, (string) $legacyId, 'failed', $e->getMessage());
                        }
                    }

                    $bar->advance();
                }

                if (! $dryRun) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (Throwable $e) {
                DB::rollBack();
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
     * Map a MySQL row to the PostgreSQL schema for the given target table.
     * Returns null to skip the row entirely.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapMysqlRowToPostgres(string $target, array $row): ?array
    {
        return match ($target) {
            'contacts' => $this->mapContact($row),
            'projects' => $this->mapProject($row),
            'lots' => $this->mapLot($row),
            default => null,
        };
    }

    /** @param array<string, mixed> $row */
    private function mapContact(array $row): array
    {
        return [
            'legacy_lead_id' => $row['id'],
            'first_name' => $row['first_name'] ?? $row['name'] ?? 'Unknown',
            'last_name' => $row['last_name'] ?? null,
            'job_title' => $row['job_title'] ?? $row['occupation'] ?? null,
            'type' => (! empty($row['is_partner']) && (bool) $row['is_partner']) ? 'partner' : 'lead',
            'stage' => $row['stage'] ?? $row['status'] ?? null,
            'company_name' => $row['company'] ?? $row['company_name'] ?? null,
            'last_followup_at' => $row['last_followup_at'] ?? null,
            'next_followup_at' => $row['next_followup_at'] ?? null,
            'last_contacted_at' => $row['last_contacted_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function mapProject(array $row): array
    {
        return [
            'legacy_id' => $row['id'],
            'name' => $row['name'] ?? $row['project_name'] ?? 'Unknown',
            'slug' => $row['slug'] ?? null,
            'status' => $row['status'] ?? null,
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    /** @param array<string, mixed> $row */
    private function mapLot(array $row): array
    {
        return [
            'legacy_id' => $row['id'],
            'lot_number' => $row['lot_number'] ?? $row['lot_no'] ?? null,
            'title' => $row['title'] ?? $row['name'] ?? null,
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
