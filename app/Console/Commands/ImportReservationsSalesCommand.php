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
                    'purchase_price' => $row['purchase_price'] ?? null,
                    'deposit' => $row['deposit'] ?? null,
                    'deposit_bal' => $row['deposit_bal'] ?? null,
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

        return $this->runImport(
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
                    'piab_comm' => $row['piab_comm'] ?? null,
                    'subscriber_comm' => $row['subscriber_comm'] ?? null,
                    'sales_agent_comm' => $row['sales_agent_comm'] ?? null,
                    'bdm_comm' => $row['bdm_comm'] ?? null,
                    'referral_partner_comm' => $row['referral_partner_comm'] ?? null,
                    'affiliate_comm' => $row['affiliate_comm'] ?? null,
                    'agent_comm' => $row['agent_comm'] ?? null,
                    'comms_in_total' => $row['comms_in_total'] ?? null,
                    'comms_out_total' => $row['comms_out_total'] ?? null,
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
        );
    }

    private function importCommissions(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing commissions...');

        // Build sale legacy_id → new sale id map
        $saleMap = DB::table('sales')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $query = DB::connection('mysql_legacy')->table('commissions');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $this->runImport(
            query: $query,
            logKey: 'commissions',
            dryRun: $dryRun,
            chunk: $chunk,
            force: $force,
            mapRow: function (array $row) use ($saleMap): ?array {
                $saleId = $saleMap[$row['sale_id'] ?? 0] ?? null;
                if ($saleId === null) {
                    Log::warning("fusion:import commissions: no sale found for legacy sale_id {$row['sale_id']}");

                    return null;
                }

                return [
                    'sale_id' => $saleId,
                    'commission_type' => $row['commission_type'] ?? 'piab',
                    'rate_percentage' => $row['rate_percentage'] ?? null,
                    'amount' => $row['amount'] ?? 0,
                    'override_amount' => (bool) ($row['override_amount'] ?? false),
                    'notes' => $row['notes'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertKey: null,
            targetTable: 'commissions',
        );
    }

    /**
     * @param  callable(array): ?array  $mapRow
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
    ): int {
        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use (
            $dryRun, $logKey, $mapRow, $upsertKey, $targetTable,
            &$processed, &$skipped, &$failed, $bar
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
                            if ($upsertKey !== null) {
                                DB::table($targetTable)->updateOrInsert(
                                    [$upsertKey => $mapped[$upsertKey]],
                                    $mapped
                                );
                            } else {
                                DB::table($targetTable)->insert($mapped);
                            }
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
        $this->info("[{$logKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed;
    }
}
