<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportLoginHistoriesCommand extends Command
{
    protected $signature = 'fusion:import-login-histories
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--force : Re-import (truncate login_events first)}';

    protected $description = 'Import login histories from MySQL legacy DB into PostgreSQL login_events table.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        // Build user map via email matching
        $userMap = $this->buildUserMap();
        $this->info('User map loaded: '.count($userMap).' matched users');

        if ($force && ! $dryRun) {
            DB::table('login_events')->truncate();
            $this->info('Truncated login_events table.');
        }

        try {
            $total = DB::connection('mysql_legacy')->table('login_histories')->count();
        } catch (Throwable $e) {
            $this->warn("Could not access legacy login_histories: {$e->getMessage()}");

            return self::FAILURE;
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('mysql_legacy')
            ->table('login_histories')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $userMap, &$processed, &$skipped, &$failed, $bar) {
                $batch = [];

                foreach ($rows as $row) {
                    try {
                        $userId = $userMap[(int) ($row->user_id ?? 0)] ?? null;

                        if ($userId === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        $batch[] = [
                            'user_id' => $userId,
                            'ip_address' => $row->ip_address ?? $row->ip ?? null,
                            'user_agent' => $row->user_agent ?? null,
                            'device_fingerprint' => null,
                            'browser_name' => $row->browser_name ?? $row->browser ?? null,
                            'browser_version' => $row->browser_version ?? null,
                            'os_name' => $row->os_name ?? $row->platform ?? null,
                            'os_version' => $row->os_version ?? null,
                            'device_type' => $row->device_type ?? $row->device ?? null,
                            'created_at' => $row->created_at ?? $row->login_at ?? now(),
                        ];

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-login-histories row {$row->id} failed: {$e->getMessage()}");
                    }

                    $bar->advance();
                }

                if (! $dryRun && $batch !== []) {
                    try {
                        DB::table('login_events')->insert($batch);
                    } catch (Throwable $e) {
                        foreach ($batch as $record) {
                            try {
                                DB::table('login_events')->insert($record);
                            } catch (Throwable $innerE) {
                                $failed++;
                                $processed--;
                                Log::warning("fusion:import-login-histories single insert failed: {$innerE->getMessage()}");
                            }
                        }
                    }
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Build v3 user_id → v4 user_id map via email matching.
     *
     * @return array<int, int>
     */
    private function buildUserMap(): array
    {
        try {
            $legacyUsers = DB::connection('mysql_legacy')
                ->table('users')
                ->pluck('email', 'id')
                ->all();

            $v4Users = DB::table('users')
                ->pluck('id', 'email')
                ->all();

            $map = [];
            foreach ($legacyUsers as $legacyId => $email) {
                if (isset($v4Users[$email])) {
                    $map[(int) $legacyId] = (int) $v4Users[$email];
                }
            }

            return $map;
        } catch (Throwable) {
            return [];
        }
    }
}
