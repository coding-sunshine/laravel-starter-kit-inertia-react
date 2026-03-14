<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportContactsCommand extends Command
{
    protected $signature = 'fusion:import-contacts
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}
                            {--table=all : Which tables to import: all, contacts, sources, companies}';

    protected $description = 'Import contacts (leads), sources, and companies from MySQL legacy DB into PostgreSQL. Builds the legacy_lead_id → contact_id map.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $since = $this->option('since');
        $force = (bool) $this->option('force');
        $table = $this->option('table');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        $overallFailed = 0;

        if ($table === 'all' || $table === 'sources') {
            $overallFailed += $this->importSources($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'companies') {
            $overallFailed += $this->importCompanies($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'contacts') {
            $overallFailed += $this->importContacts($dryRun, $chunk, $since, $force);
        }

        if ($overallFailed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function importSources(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing sources...');

        return $this->runImport(
            sourceTable: 'sources',
            targetTable: 'sources',
            logKey: 'sources',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? $row['source'] ?? 'Unknown',
                    'slug' => $row['slug'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data): void {
                DB::table('sources')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importCompanies(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing companies...');

        return $this->runImport(
            sourceTable: 'companies',
            targetTable: 'companies',
            logKey: 'companies',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? 'Unknown',
                    'website' => $row['website'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'notes' => $row['notes'] ?? $row['description'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data): void {
                DB::table('companies')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importContacts(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing contacts (leads)...');

        // Build source legacy_id → new source_id map
        $sourceMap = DB::table('sources')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        // Build company legacy_id → new company_id map
        $companyMap = DB::table('companies')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $failed = $this->runImport(
            sourceTable: 'leads',
            targetTable: 'contacts',
            logKey: 'contacts',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($sourceMap, $companyMap): ?array {
                $type = 'lead';
                if (! empty($row['is_partner']) && (bool) $row['is_partner']) {
                    $type = 'partner';
                }

                $sourceId = null;
                if (! empty($row['source_id'])) {
                    $sourceId = $sourceMap[$row['source_id']] ?? null;
                }

                $companyId = null;
                if (! empty($row['company_id'])) {
                    $companyId = $companyMap[$row['company_id']] ?? null;
                }

                return [
                    'legacy_lead_id' => $row['id'],
                    'contact_origin' => 'property',
                    'first_name' => $row['first_name'] ?? $row['name'] ?? 'Unknown',
                    'last_name' => $row['last_name'] ?? null,
                    'job_title' => $row['job_title'] ?? $row['occupation'] ?? null,
                    'type' => $type,
                    'stage' => $row['stage'] ?? $row['status'] ?? null,
                    'source_id' => $sourceId,
                    'company_id' => $companyId,
                    'company_name' => $row['company'] ?? $row['company_name'] ?? null,
                    'extra_attributes' => null,
                    'last_followup_at' => $row['last_followup_at'] ?? $row['last_followup'] ?? null,
                    'next_followup_at' => $row['next_followup_at'] ?? $row['next_followup'] ?? null,
                    'last_contacted_at' => $row['last_contacted_at'] ?? null,
                    'lead_score' => null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data): void {
                DB::table('contacts')->updateOrInsert(
                    ['legacy_lead_id' => $data['legacy_lead_id']],
                    $data
                );
            },
            force: $force,
        );

        if (! $dryRun) {
            $this->importContactEmails($chunk, $sourceMap, $companyMap);
            $this->importContactPhones($chunk);
        }

        return $failed;
    }

    private function importContactEmails(int $chunk, array $sourceMap, array $companyMap): void
    {
        $this->info('Importing contact emails...');

        // Build legacy_lead_id → contact_id map
        $contactMap = DB::table('contacts')
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();

        $count = 0;
        DB::connection('mysql_legacy')
            ->table('contacts')
            ->where('type', 'email')
            ->where('model_type', 'App\\Models\\Lead')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($contactMap, &$count) {
                DB::beginTransaction();
                try {
                    foreach ($rows as $row) {
                        $leadId = $row->model_id ?? null;
                        $contactId = $leadId ? ($contactMap[$leadId] ?? null) : null;
                        if ($contactId === null) {
                            continue;
                        }

                        DB::table('contact_emails')->updateOrInsert(
                            ['contact_id' => $contactId, 'value' => $row->value ?? $row->contact ?? ''],
                            [
                                'contact_id' => $contactId,
                                'type' => $row->label ?? 'work',
                                'value' => $row->value ?? $row->contact ?? '',
                                'is_primary' => (bool) ($row->is_primary ?? false),
                                'order_column' => $row->order ?? 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $count++;
                    }
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    Log::warning('fusion:import-contacts [emails] chunk failed: '.$e->getMessage());
                }
            });

        $this->info("  [contact_emails] Imported: {$count}");
    }

    private function importContactPhones(int $chunk): void
    {
        $this->info('Importing contact phones...');

        // Build legacy_lead_id → contact_id map
        $contactMap = DB::table('contacts')
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();

        $count = 0;
        DB::connection('mysql_legacy')
            ->table('contacts')
            ->whereIn('type', ['mobile', 'phone', 'work_phone', 'home_phone'])
            ->where('model_type', 'App\\Models\\Lead')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($contactMap, &$count) {
                DB::beginTransaction();
                try {
                    foreach ($rows as $row) {
                        $leadId = $row->model_id ?? null;
                        $contactId = $leadId ? ($contactMap[$leadId] ?? null) : null;
                        if ($contactId === null) {
                            continue;
                        }

                        DB::table('contact_phones')->updateOrInsert(
                            ['contact_id' => $contactId, 'value' => $row->value ?? $row->contact ?? ''],
                            [
                                'contact_id' => $contactId,
                                'type' => $row->type ?? $row->label ?? 'mobile',
                                'value' => $row->value ?? $row->contact ?? '',
                                'is_primary' => (bool) ($row->is_primary ?? false),
                                'order_column' => $row->order ?? 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $count++;
                    }
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    Log::warning('fusion:import-contacts [phones] chunk failed: '.$e->getMessage());
                }
            });

        $this->info("  [contact_phones] Imported: {$count}");
    }

    /**
     * Generic import runner following the base import command pattern.
     */
    private function runImport(
        string $sourceTable,
        string $targetTable,
        string $logKey,
        bool $dryRun,
        int $chunk,
        ?string $since,
        Closure $mapRow,
        Closure $upsertRow,
        bool $force,
    ): int {
        try {
            $query = DB::connection('mysql_legacy')->table($sourceTable);
        } catch (Exception $e) {
            $this->warn("  [{$logKey}] Could not connect to legacy DB or table not found: {$e->getMessage()}");

            return 0;
        }

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use (
            $dryRun, $mapRow, $upsertRow, &$processed, &$skipped, &$failed, $bar, $logKey
        ) {
            DB::beginTransaction();
            try {
                foreach ($rows as $row) {
                    try {
                        $mapped = $mapRow((array) $row);
                        if ($mapped === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }
                        if (! $dryRun) {
                            $upsertRow($mapped);
                        }
                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import [{$logKey}] row {$row->id} failed: {$e->getMessage()}");
                    }
                    $bar->advance();
                }
                if (! $dryRun) {
                    DB::commit();
                }
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error("Chunk failed: {$e->getMessage()}");
                $failed += count($rows);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("  [{$logKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed;
    }
}
