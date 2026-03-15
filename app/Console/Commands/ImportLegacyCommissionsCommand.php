<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportLegacyCommissionsCommand extends Command
{
    protected $signature = 'fusion:import-legacy-commissions
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--force : Re-import existing rows}';

    protected $description = 'Import legacy polymorphic commissions (on Projects/Lots) from MySQL legacy DB into PostgreSQL.';

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

        $projectMap = DB::table('projects')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $lotMap = DB::table('lots')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $this->info('Maps loaded: projects='.count($projectMap).', lots='.count($lotMap));

        $total = DB::connection('mysql_legacy')->table('commissions')->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('mysql_legacy')
            ->table('commissions')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $force, $projectMap, $lotMap, &$processed, &$skipped, &$failed, $bar) {
                foreach ($rows as $row) {
                    try {
                        // Skip if already imported and not forcing
                        if (! $force) {
                            $exists = DB::table('commissions')
                                ->where('legacy_id', $row->id)
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                $bar->advance();

                                continue;
                            }
                        }

                        // Resolve polymorphic target
                        $commissionableType = null;
                        $commissionableId = null;

                        $v3Type = $row->commissionable_type ?? null;
                        $v3Id = $row->commissionable_id ?? null;

                        if ($v3Type !== null && $v3Id !== null) {
                            if (str_contains($v3Type, 'Project')) {
                                $commissionableType = 'App\\Models\\Project';
                                $commissionableId = $projectMap[(int) $v3Id] ?? null;
                            } elseif (str_contains($v3Type, 'Lot')) {
                                $commissionableType = 'App\\Models\\Lot';
                                $commissionableId = $lotMap[(int) $v3Id] ?? null;
                            }
                        }

                        if ($commissionableId === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        $data = [
                            'legacy_id' => $row->id,
                            'sale_id' => null,
                            'commissionable_type' => $commissionableType,
                            'commissionable_id' => $commissionableId,
                            'commission_type' => 'piab',
                            'agent_user_id' => null,
                            'rate_percentage' => $row->commission_percent_in ?? null,
                            'amount' => $row->commission_in ?? 0,
                            'override_amount' => false,
                            'notes' => null,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            if ($force) {
                                DB::table('commissions')->updateOrInsert(
                                    ['legacy_id' => $data['legacy_id']],
                                    $data
                                );
                            } else {
                                DB::table('commissions')->insert($data);
                            }
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-legacy-commissions row {$row->id} failed: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
