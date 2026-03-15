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

        // Build lookup sets for contact type derivation from v3 relationships
        $agentContactIds = $this->buildAgentContactIds();
        $developerContactIds = $this->buildDeveloperContactIds();
        $subscriberLeadIds = $this->buildSubscriberLeadIds();

        // Build source name map for origin derivation (v3 uses 'label' not 'name')
        $sourceNameMap = DB::connection('mysql_legacy')
            ->table('sources')
            ->pluck('label', 'id')
            ->all();

        $failed = $this->runImport(
            sourceTable: 'leads',
            targetTable: 'contacts',
            logKey: 'contacts',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($sourceMap, $companyMap, $agentContactIds, $developerContactIds, $subscriberLeadIds, $sourceNameMap): ?array {
                $type = $this->deriveContactType($row, $agentContactIds, $developerContactIds, $subscriberLeadIds);
                $origin = $this->deriveContactOrigin($row, $sourceNameMap);

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
                    'organization_id' => 1,
                    'contact_origin' => $origin,
                    'first_name' => $row['first_name'] ?? 'Unknown',
                    'last_name' => $row['last_name'] ?? null,
                    'job_title' => $row['job_title'] ?? null,
                    'type' => $type,
                    'stage' => $row['stage'] ?? null,
                    'source_id' => $sourceId,
                    'company_id' => $companyId,
                    'company_name' => null,
                    'extra_attributes' => ! empty($row['extra_attributes']) ? $row['extra_attributes'] : null,
                    'last_followup_at' => $row['last_followup_at'] ?? null,
                    'next_followup_at' => $row['next_followup_at'] ?? null,
                    'last_contacted_at' => null,
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

    /**
     * Derive contact type from v3 relationships.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, bool>  $agentContactIds
     * @param  array<int, bool>  $developerContactIds
     * @param  array<int, bool>  $subscriberLeadIds
     */
    private function deriveContactType(array $row, array $agentContactIds, array $developerContactIds, array $subscriberLeadIds): string
    {
        $leadId = (int) $row['id'];

        if (! empty($row['is_partner']) && (bool) $row['is_partner']) {
            return 'partner';
        }

        if (isset($developerContactIds[$leadId])) {
            return 'developer_rep';
        }

        if (isset($agentContactIds[$leadId])) {
            return 'sales_agent';
        }

        if (isset($subscriberLeadIds[$leadId])) {
            return 'subscriber';
        }

        return 'lead';
    }

    /**
     * Derive contact origin from v3 source name.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $sourceNameMap
     */
    private function deriveContactOrigin(array $row, array $sourceNameMap): string
    {
        if (empty($row['source_id'])) {
            return 'manual';
        }

        $sourceName = $sourceNameMap[$row['source_id']] ?? null;
        if ($sourceName === null) {
            return 'manual';
        }

        $sourceName = mb_strtolower($sourceName);

        return match (true) {
            str_contains($sourceName, 'website') => 'website',
            str_contains($sourceName, 'referral') => 'referral',
            str_contains($sourceName, 'phone'), str_contains($sourceName, 'call') => 'phone',
            str_contains($sourceName, 'facebook') => 'facebook_ad',
            str_contains($sourceName, 'google') => 'google_ad',
            str_contains($sourceName, 'linkedin') => 'linkedin',
            str_contains($sourceName, 'instagram') => 'instagram',
            str_contains($sourceName, 'email') => 'email',
            str_contains($sourceName, 'event'), str_contains($sourceName, 'seminar') => 'event',
            str_contains($sourceName, 'walk-in'), str_contains($sourceName, 'walk in') => 'walk_in',
            default => 'manual',
        };
    }

    /**
     * Build set of lead IDs that appear as agents in v3.
     *
     * @return array<int, bool>
     */
    private function buildAgentContactIds(): array
    {
        $ids = [];

        try {
            // Leads referenced as agents in property_reservations (column: agent_id references lead)
            $reservationAgents = DB::connection('mysql_legacy')
                ->table('property_reservations')
                ->whereNotNull('agent_id')
                ->distinct()
                ->pluck('agent_id');

            foreach ($reservationAgents as $id) {
                $ids[(int) $id] = true;
            }

            // Sales agents referenced by lead_id via users table
            $salesAgentUserIds = DB::connection('mysql_legacy')
                ->table('sales')
                ->whereNotNull('sales_agent_id')
                ->distinct()
                ->pluck('sales_agent_id');

            // sales_agent_id may reference leads directly or users — map users→leads
            if ($salesAgentUserIds->isNotEmpty()) {
                $userLeadMap = DB::connection('mysql_legacy')
                    ->table('users')
                    ->whereIn('id', $salesAgentUserIds)
                    ->whereNotNull('lead_id')
                    ->pluck('lead_id');

                foreach ($userLeadMap as $leadId) {
                    $ids[(int) $leadId] = true;
                }
            }
        } catch (Throwable) {
            // Tables may not exist in legacy
        }

        return $ids;
    }

    /**
     * Build set of lead IDs linked to developers in v3.
     * Developers have user_id → users.lead_id → leads.id
     *
     * @return array<int, bool>
     */
    private function buildDeveloperContactIds(): array
    {
        $ids = [];

        try {
            $developerUserIds = DB::connection('mysql_legacy')
                ->table('developers')
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');

            if ($developerUserIds->isNotEmpty()) {
                $leadIds = DB::connection('mysql_legacy')
                    ->table('users')
                    ->whereIn('id', $developerUserIds)
                    ->whereNotNull('lead_id')
                    ->pluck('lead_id');

                foreach ($leadIds as $id) {
                    $ids[(int) $id] = true;
                }
            }
        } catch (Throwable) {
            // Table may not exist
        }

        return $ids;
    }

    /**
     * Build set of lead IDs that are subscribers in v3.
     * subscribers: model_has_roles → users.lead_id → leads.id
     *
     * @return array<int, bool>
     */
    private function buildSubscriberLeadIds(): array
    {
        $ids = [];

        try {
            $subscriberUserIds = DB::connection('mysql_legacy')
                ->table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'subscriber')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->distinct()
                ->pluck('model_has_roles.model_id');

            // Map user_id → lead_id via users table
            if ($subscriberUserIds->isNotEmpty()) {
                $leadIds = DB::connection('mysql_legacy')
                    ->table('users')
                    ->whereIn('id', $subscriberUserIds)
                    ->whereNotNull('lead_id')
                    ->pluck('lead_id');

                foreach ($leadIds as $leadId) {
                    $ids[(int) $leadId] = true;
                }
            }
        } catch (Throwable) {
            // Tables may not exist
        }

        return $ids;
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
            ->whereIn('type', ['email', 'email_1', 'email_2'])
            ->where('model_type', 'App\\Models\\Lead')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($contactMap, &$count) {
                foreach ($rows as $row) {
                    try {
                        $leadId = $row->model_id ?? null;
                        $contactId = $leadId ? ($contactMap[$leadId] ?? null) : null;
                        if ($contactId === null) {
                            continue;
                        }

                        DB::transaction(function () use ($row, $contactId) {
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
                        });
                        $count++;
                    } catch (Throwable $e) {
                        Log::warning("fusion:import-contacts [emails] row {$row->id} failed: {$e->getMessage()}");
                    }
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
                foreach ($rows as $row) {
                    try {
                        $leadId = $row->model_id ?? null;
                        $contactId = $leadId ? ($contactMap[$leadId] ?? null) : null;
                        if ($contactId === null) {
                            continue;
                        }

                        DB::transaction(function () use ($row, $contactId) {
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
                        });
                        $count++;
                    } catch (Throwable $e) {
                        Log::warning("fusion:import-contacts [phones] row {$row->id} failed: {$e->getMessage()}");
                    }
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
            foreach ($rows as $row) {
                try {
                    $mapped = $mapRow((array) $row);
                    if ($mapped === null) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }
                    if (! $dryRun) {
                        DB::transaction(fn () => $upsertRow($mapped));
                    }
                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning("fusion:import [{$logKey}] row {$row->id} failed: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("  [{$logKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed;
    }
}
