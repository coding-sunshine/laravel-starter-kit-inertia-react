<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportReservationsSalesCommand extends Command
{
    protected $signature = 'fusion:import-reservations-sales
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}
                            {--table=all : Which tables to import: all, reservations, enquiries, searches, sales, commissions}';

    protected $description = 'Import property reservations, enquiries, searches, sales, and commissions from MySQL legacy DB into PostgreSQL.';

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

        $contactMap = $this->buildLeadContactMap();
        $lotMap = $this->buildLegacyLotMap();
        $projectMap = $this->buildLegacyProjectMap();

        $this->info('Maps loaded: contacts='.count($contactMap).', lots='.count($lotMap).', projects='.count($projectMap));

        $overallFailed = 0;

        if ($table === 'all' || $table === 'reservations') {
            $overallFailed += $this->importReservations($dryRun, $chunk, $since, $force, $contactMap, $lotMap, $projectMap);
        }

        if ($table === 'all' || $table === 'enquiries') {
            $overallFailed += $this->importEnquiries($dryRun, $chunk, $since, $force, $contactMap, $lotMap, $projectMap);
        }

        if ($table === 'all' || $table === 'searches') {
            $overallFailed += $this->importSearches($dryRun, $chunk, $since, $force, $contactMap);
        }

        if ($table === 'all' || $table === 'sales') {
            $overallFailed += $this->importSales($dryRun, $chunk, $since, $force, $contactMap, $lotMap, $projectMap);
        }

        if ($table === 'all' || $table === 'commissions') {
            $overallFailed += $this->importCommissions($dryRun, $chunk, $since, $force);
        }

        if ($overallFailed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Build the lead_id → contact_id map from contacts.legacy_lead_id.
     *
     * @return array<int, int>
     */
    private function buildLeadContactMap(): array
    {
        return DB::table('contacts')
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();
    }

    /**
     * Build legacy lot_id → new lot_id map.
     *
     * @return array<int, int>
     */
    private function buildLegacyLotMap(): array
    {
        return DB::table('lots')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();
    }

    /**
     * Build legacy project_id → new project_id map.
     *
     * @return array<int, int>
     */
    private function buildLegacyProjectMap(): array
    {
        return DB::table('projects')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();
    }

    /**
     * @param  array<int, int>  $contactMap
     * @param  array<int, int>  $lotMap
     * @param  array<int, int>  $projectMap
     */
    private function importReservations(bool $dryRun, int $chunk, ?string $since, bool $force, array $contactMap, array $lotMap, array $projectMap): int
    {
        $this->info('Importing property reservations...');

        $query = DB::connection('mysql_legacy')->table('property_reservations');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $this->runImport(
            query: $query,
            logKey: 'reservations',
            dryRun: $dryRun,
            chunk: $chunk,
            force: $force,
            mapRow: function (array $row) use ($contactMap, $lotMap, $projectMap): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'agent_contact_id' => isset($row['agent_id']) ? ($contactMap[$row['agent_id']] ?? null) : null,
                    'primary_contact_id' => isset($row['purchaser1_id']) ? ($contactMap[$row['purchaser1_id']] ?? null) : null,
                    'secondary_contact_id' => isset($row['purchaser2_id']) ? ($contactMap[$row['purchaser2_id']] ?? null) : null,
                    'lot_id' => isset($row['lot_id']) ? ($lotMap[$row['lot_id']] ?? null) : null,
                    'project_id' => isset($row['project_id']) ? ($projectMap[$row['project_id']] ?? null) : null,
                    'stage' => $row['stage'] ?? 'enquiry',
                    'purchase_price' => $this->numericOrNull($row['purchase_price'] ?? null),
                    'deposit' => $this->numericOrNull($row['deposit'] ?? null),
                    'deposit_bal' => $this->numericOrNull($row['deposit_bal'] ?? null),
                    'deposit_status' => $row['deposit_status'] ?? 'pending',
                    'finance_condition' => (bool) ($row['finance_condition'] ?? false),
                    'finance_days' => $row['finance_days'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertKey: 'legacy_id',
            targetTable: 'property_reservations',
        );
    }

    /**
     * @param  array<int, int>  $contactMap
     * @param  array<int, int>  $lotMap
     * @param  array<int, int>  $projectMap
     */
    private function importEnquiries(bool $dryRun, int $chunk, ?string $since, bool $force, array $contactMap, array $lotMap, array $projectMap): int
    {
        $this->info('Importing property enquiries...');

        $query = DB::connection('mysql_legacy')->table('property_enquiries');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $this->runImport(
            query: $query,
            logKey: 'enquiries',
            dryRun: $dryRun,
            chunk: $chunk,
            force: $force,
            mapRow: function (array $row) use ($contactMap, $lotMap, $projectMap): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'client_contact_id' => isset($row['client_id']) ? ($contactMap[$row['client_id']] ?? null) : null,
                    'agent_contact_id' => isset($row['agent_id']) ? ($contactMap[$row['agent_id']] ?? null) : null,
                    'lot_id' => isset($row['lot_id']) ? ($lotMap[$row['lot_id']] ?? null) : null,
                    'project_id' => isset($row['project_id']) ? ($projectMap[$row['project_id']] ?? null) : null,
                    'status' => $row['status'] ?? 'new',
                    'message' => $row['message'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertKey: 'legacy_id',
            targetTable: 'property_enquiries',
        );
    }

    /**
     * @param  array<int, int>  $contactMap
     */
    private function importSearches(bool $dryRun, int $chunk, ?string $since, bool $force, array $contactMap): int
    {
        $this->info('Importing property searches...');

        $query = DB::connection('mysql_legacy')->table('property_searches');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $this->runImport(
            query: $query,
            logKey: 'searches',
            dryRun: $dryRun,
            chunk: $chunk,
            force: $force,
            mapRow: function (array $row) use ($contactMap): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'client_contact_id' => isset($row['client_id']) ? ($contactMap[$row['client_id']] ?? null) : null,
                    'agent_contact_id' => isset($row['agent_id']) ? ($contactMap[$row['agent_id']] ?? null) : null,
                    'budget_min' => $row['budget_min'] ?? null,
                    'budget_max' => $row['budget_max'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertKey: 'legacy_id',
            targetTable: 'property_searches',
        );
    }

    /**
     * @param  array<int, int>  $contactMap
     * @param  array<int, int>  $lotMap
     * @param  array<int, int>  $projectMap
     */
    private function importSales(bool $dryRun, int $chunk, ?string $since, bool $force, array $contactMap, array $lotMap, array $projectMap): int
    {
        $this->info('Importing sales...');

        $query = DB::connection('mysql_legacy')->table('sales');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $commissionCount = 0;

        $result = $this->runImport(
            query: $query,
            logKey: 'sales',
            dryRun: $dryRun,
            chunk: $chunk,
            force: $force,
            mapRow: function (array $row) use ($contactMap, $lotMap, $projectMap): ?array {
                if (($row['is_test'] ?? false)) {
                    return null;
                }

                return [
                    'legacy_id' => $row['id'],
                    'client_contact_id' => isset($row['client_id']) ? ($contactMap[$row['client_id']] ?? null) : null,
                    'sales_agent_contact_id' => isset($row['sales_agent_id']) ? ($contactMap[$row['sales_agent_id']] ?? null) : null,
                    'subscriber_contact_id' => isset($row['subscriber_id']) ? ($contactMap[$row['subscriber_id']] ?? null) : null,
                    'bdm_contact_id' => isset($row['bdm_id']) ? ($contactMap[$row['bdm_id']] ?? null) : null,
                    'referral_partner_contact_id' => isset($row['referral_partner_id']) ? ($contactMap[$row['referral_partner_id']] ?? null) : null,
                    'affiliate_contact_id' => isset($row['affiliate_id']) ? ($contactMap[$row['affiliate_id']] ?? null) : null,
                    'agent_contact_id' => isset($row['agent_id']) ? ($contactMap[$row['agent_id']] ?? null) : null,
                    'lot_id' => isset($row['lot_id']) ? ($lotMap[$row['lot_id']] ?? null) : null,
                    'project_id' => isset($row['project_id']) ? ($projectMap[$row['project_id']] ?? null) : null,
                    'status' => $row['status'] ?? 'active',
                    'comm_in_notes' => $row['comm_in_notes'] ?? null,
                    'comm_out_notes' => $row['comm_out_notes'] ?? null,
                    'piab_comm' => $this->numericOrNull($row['piab_comm'] ?? null),
                    'subscriber_comm' => $this->numericOrNull($row['subscriber_comm'] ?? null),
                    'sales_agent_comm' => $this->numericOrNull($row['sales_agent_comm'] ?? null),
                    'bdm_comm' => $this->numericOrNull($row['bdm_comm'] ?? null),
                    'referral_partner_comm' => $this->numericOrNull($row['referral_partner_comm'] ?? null),
                    'affiliate_comm' => $this->numericOrNull($row['affiliate_comm'] ?? null),
                    'agent_comm' => $this->numericOrNull($row['agent_comm'] ?? null),
                    'comms_in_total' => $this->numericOrNull($row['comms_in_total'] ?? null),
                    'comms_out_total' => $this->numericOrNull($row['comms_out_total'] ?? null),
                    'settled_at' => $row['settled_at'] ?? null,
                    'summary_note' => $row['summary_note'] ?? null,
                    'is_comments_enabled' => (bool) ($row['is_comments_enabled'] ?? true),
                    'is_sas_enabled' => (bool) ($row['is_sas_enabled'] ?? false),
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertKey: 'legacy_id',
            targetTable: 'sales',
            afterUpsert: $dryRun ? null : function (array $mapped) use (&$commissionCount): void {
                // Generate commission records from the sale's own commission columns
                $sale = DB::table('sales')->where('legacy_id', $mapped['legacy_id'])->first();
                if (! $sale) {
                    return;
                }

                $commissionTypes = [
                    'piab' => 'piab_comm',
                    'affiliate' => 'affiliate_comm',
                    'subscriber' => 'subscriber_comm',
                    'sales_agent' => 'sales_agent_comm',
                    'bdm' => 'bdm_comm',
                    'referral_partner' => 'referral_partner_comm',
                    'agent' => 'agent_comm',
                ];

                foreach ($commissionTypes as $type => $column) {
                    $amount = $mapped[$column] ?? null;
                    if ($amount !== null && $amount > 0) {
                        DB::table('commissions')->updateOrInsert(
                            ['sale_id' => $sale->id, 'commission_type' => $type],
                            [
                                'sale_id' => $sale->id,
                                'commission_type' => $type,
                                'amount' => $amount,
                                'rate_percentage' => null,
                                'override_amount' => false,
                                'created_at' => $mapped['created_at'],
                                'updated_at' => $mapped['updated_at'],
                            ]
                        );
                        $commissionCount++;
                    }
                }
            },
        );

        if (! $dryRun) {
            $this->info("[commissions] Generated from sales columns: {$commissionCount}");
        }

        return $result;
    }

    /**
     * @deprecated Commission records are now generated from sales columns in importSales().
     *             This method is kept for backwards compatibility with the --table=commissions flag.
     */
    private function importCommissions(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Generating commissions from imported sales...');

        if ($dryRun) {
            $this->info('[DRY RUN] Skipping commission generation.');

            return 0;
        }

        $commissionCount = 0;
        $failed = 0;

        $commissionTypes = [
            'piab' => 'piab_comm',
            'affiliate' => 'affiliate_comm',
            'subscriber' => 'subscriber_comm',
            'sales_agent' => 'sales_agent_comm',
            'bdm' => 'bdm_comm',
            'referral_partner' => 'referral_partner_comm',
            'agent' => 'agent_comm',
        ];

        $total = DB::table('sales')->whereNotNull('legacy_id')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('sales')->whereNotNull('legacy_id')->orderBy('id')->chunk($chunk, function ($sales) use (
            $commissionTypes, &$commissionCount, &$failed, $bar
        ) {
            foreach ($sales as $sale) {
                try {
                    DB::transaction(function () use ($sale, $commissionTypes, &$commissionCount) {
                        foreach ($commissionTypes as $type => $column) {
                            $amount = $sale->$column ?? null;
                            if ($amount !== null && $amount > 0) {
                                DB::table('commissions')->updateOrInsert(
                                    ['sale_id' => $sale->id, 'commission_type' => $type],
                                    [
                                        'sale_id' => $sale->id,
                                        'commission_type' => $type,
                                        'amount' => (float) $amount,
                                        'rate_percentage' => null,
                                        'override_amount' => false,
                                        'created_at' => $sale->created_at ?? now(),
                                        'updated_at' => $sale->updated_at ?? now(),
                                    ]
                                );
                                $commissionCount++;
                            }
                        }
                    });
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning("fusion:import [commissions] sale {$sale->id} failed: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("[commissions] Generated: {$commissionCount} | Failed: {$failed}");

        return $failed;
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === ' ') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  callable(array): ?array  $mapRow
     * @param  (callable(array): void)|null  $afterUpsert
     */
    private function runImport(
        \Illuminate\Database\Query\Builder $query,
        string $logKey,
        bool $dryRun,
        int $chunk,
        bool $force,
        callable $mapRow,
        ?string $upsertKey,
        string $targetTable,
        ?callable $afterUpsert = null,
    ): int {
        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use (
            $dryRun, $logKey, $mapRow, $upsertKey, $targetTable, $afterUpsert,
            &$processed, &$skipped, &$failed, $bar
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
                        DB::transaction(function () use ($targetTable, $upsertKey, $mapped, $afterUpsert): void {
                            if ($upsertKey !== null) {
                                DB::table($targetTable)->updateOrInsert(
                                    [$upsertKey => $mapped[$upsertKey]],
                                    $mapped
                                );
                            } else {
                                DB::table($targetTable)->insert($mapped);
                            }

                            if ($afterUpsert !== null) {
                                $afterUpsert($mapped);
                            }
                        });
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
        $this->info("[{$logKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed;
    }
}
