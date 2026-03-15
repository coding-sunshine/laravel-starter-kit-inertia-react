<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ImportWebsitesCommand extends Command
{
    protected $signature = 'fusion:import-websites
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--table=all : Which tables to import: all, websites, towns, services}
                            {--force : Re-import existing rows}';

    protected $description = 'Import websites, AU towns, and services from MySQL legacy DB into PostgreSQL.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $table = $this->option('table');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        $overallFailed = 0;

        if ($table === 'all' || $table === 'towns') {
            $overallFailed += $this->importTowns($dryRun, $chunk, $force);
        }

        if ($table === 'all' || $table === 'services') {
            $overallFailed += $this->importServices($dryRun, $chunk, $force);
        }

        if ($table === 'all' || $table === 'websites') {
            $overallFailed += $this->importWebsites($dryRun, $chunk, $force);
        }

        if ($overallFailed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function importTowns(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing AU towns...');

        try {
            $total = DB::connection('mysql_legacy')->table('au_towns')->count();
        } catch (Throwable $e) {
            $this->warn("  Could not access legacy au_towns: {$e->getMessage()}");

            return 0;
        }

        $processed = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('mysql_legacy')
            ->table('au_towns')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$processed, &$failed, $bar) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'legacy_id' => $row->id,
                            'name' => $row->name ?? '',
                            'state_id' => $row->state_id ?? null,
                            'postcode' => $row->postcode ?? null,
                            'lat' => $row->lat ?? null,
                            'lng' => $row->lng ?? $row->lon ?? null,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('au_towns')->updateOrInsert(
                                ['legacy_id' => $data['legacy_id']],
                                $data
                            );
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-websites [towns] row {$row->id} failed: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("  [au_towns] Processed: {$processed} | Failed: {$failed}");

        return $failed;
    }

    private function importServices(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing services...');

        try {
            $total = DB::connection('mysql_legacy')->table('services')->count();
        } catch (Throwable $e) {
            $this->warn("  Could not access legacy services: {$e->getMessage()}");

            return 0;
        }

        $processed = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('mysql_legacy')
            ->table('services')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$processed, &$failed, $bar) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'legacy_id' => $row->id,
                            'name' => $row->name ?? '',
                            'slug' => $row->slug ?? Str::slug($row->name ?? 'unknown'),
                            'description' => $row->description ?? null,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('services')->updateOrInsert(
                                ['legacy_id' => $data['legacy_id']],
                                $data
                            );
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-websites [services] row {$row->id} failed: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("  [services] Processed: {$processed} | Failed: {$failed}");

        // Import company_service pivot
        $this->importCompanyServicePivot($dryRun, $chunk);

        return $failed;
    }

    private function importCompanyServicePivot(bool $dryRun, int $chunk): void
    {
        $this->info('Importing company_service pivot...');

        try {
            $total = DB::connection('mysql_legacy')->table('company_service')->count();
        } catch (Throwable) {
            $this->warn('  Could not access legacy company_service pivot.');

            return;
        }

        $companyMap = DB::table('companies')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $serviceMap = DB::table('services')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $processed = 0;

        DB::connection('mysql_legacy')
            ->table('company_service')
            ->orderBy('company_id')
            ->chunk($chunk, function ($rows) use ($dryRun, $companyMap, $serviceMap, &$processed) {
                foreach ($rows as $row) {
                    $companyId = $companyMap[(int) ($row->company_id ?? 0)] ?? null;
                    $serviceId = $serviceMap[(int) ($row->service_id ?? 0)] ?? null;

                    if ($companyId === null || $serviceId === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        DB::table('company_service')->insertOrIgnore([
                            'company_id' => $companyId,
                            'service_id' => $serviceId,
                        ]);
                    }

                    $processed++;
                }
            });

        $this->info("  [company_service] Processed: {$processed}");
    }

    private function importWebsites(bool $dryRun, int $chunk, bool $force): int
    {
        $this->info('Importing WordPress websites...');

        try {
            $total = DB::connection('mysql_legacy')->table('websites')->count();
        } catch (Throwable $e) {
            $this->warn("  Could not access legacy websites: {$e->getMessage()}");

            return 0;
        }

        $processed = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('mysql_legacy')
            ->table('websites')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$processed, &$failed, $bar) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'legacy_id' => $row->id,
                            'organization_id' => 1,
                            'title' => $row->title ?? $row->name ?? '',
                            'url' => $row->url ?? $row->domain ?? null,
                            'stage' => $row->stage ?? $row->status ?? 'active',
                            'is_enabled' => true,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('wordpress_websites')->updateOrInsert(
                                ['legacy_id' => $data['legacy_id']],
                                $data
                            );
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-websites [websites] row {$row->id} failed: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("  [websites] Processed: {$processed} | Failed: {$failed}");

        return $failed;
    }
}
