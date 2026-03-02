<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Commission;
use App\Models\Contact;
use App\Models\Developer;
use App\Models\Lot;
use App\Models\Project;
use App\Models\PropertyEnquiry;
use App\Models\PropertyReservation;
use App\Models\PropertySearch;
use App\Models\Sale;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportReservationsSalesCommand extends Command
{
    protected $signature = 'fusion:import-reservations-sales
                            {--force : Run even if legacy connection fails}
                            {--fresh : Truncate reservation/sale tables first}
                            {--organization-id= : Organization ID to assign}';

    protected $description = 'Import property_reservations, property_enquiries, property_searches, sales, commissions from MySQL legacy. Uses lead_id→contact_id map from Step 1. Run after Steps 1 and 3.';

    private ?int $organizationId = null;

    /** @var array<int, int> legacy lead_id => new contact_id */
    private array $leadToContactId = [];

    /** @var array<int, int> legacy project_id => new project_id */
    private array $projectIdMap = [];

    /** @var array<int, int> legacy lot_id => new lot_id */
    private array $lotIdMap = [];

    /** @var array<int, int> legacy developer_id => new developer_id */
    private array $developerIdMap = [];

    /** @var array<int, int> legacy sale id => new sale id */
    private array $saleIdMap = [];

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Legacy MySQL connection failed: '.$e->getMessage());
            if (! $this->option('force')) {
                return self::FAILURE;
            }
        }

        $this->organizationId = $this->option('organization-id') !== null
            ? (int) $this->option('organization-id')
            : null;

        $this->leadToContactId = Contact::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();
        $this->projectIdMap = Project::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_project_id')
            ->pluck('id', 'legacy_project_id')
            ->all();
        $this->lotIdMap = Lot::whereNotNull('legacy_lot_id')->pluck('id', 'legacy_lot_id')->all();
        $this->developerIdMap = Developer::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_developer_id')
            ->pluck('id', 'legacy_developer_id')
            ->all();

        if ($this->leadToContactId === []) {
            $this->warn('No contacts with legacy_lead_id found. Run fusion:import-contacts first (Step 1).');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncateTables();
        }

        $this->importPropertyReservations($connection);
        $this->importPropertyEnquiries($connection);
        $this->importPropertySearches($connection);
        $this->importSales($connection);
        $this->importCommissions($connection);

        $this->info('Import complete. Reservations='.PropertyReservation::withoutGlobalScope(OrganizationScope::class)->count().', Sales='.Sale::withoutGlobalScope(OrganizationScope::class)->count().', Commissions='.Commission::count());

        return self::SUCCESS;
    }

    private function truncateTables(): void
    {
        $this->info('Truncating reservation/sale tables...');
        DB::table('commissions')->delete();
        DB::table('sales')->delete();
        DB::table('property_searches')->delete();
        DB::table('property_enquiries')->delete();
        DB::table('property_reservations')->delete();
        $this->saleIdMap = [];
    }

    private function contactId(int $legacyLeadId): ?int
    {
        return $this->leadToContactId[$legacyLeadId] ?? null;
    }

    private function importPropertyReservations(string $connection): void
    {
        $this->info('Importing property_reservations...');
        $rows = DB::connection($connection)->table('property_reservations')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $agentContactId = $this->contactId((int) $row->agent_id);
            $primaryContactId = $this->contactId((int) $row->purchaser1_id);
            $newProjectId = $this->projectIdMap[(int) $row->property_id] ?? null;
            $newLotId = $this->lotIdMap[(int) $row->lot_id] ?? null;
            if ($agentContactId === null || $primaryContactId === null || $newProjectId === null || $newLotId === null) {
                $skipped++;
                continue;
            }
            $secondaryContactId = $row->purchaser2_id ? $this->contactId((int) $row->purchaser2_id) : null;
            PropertyReservation::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'agent_contact_id' => $agentContactId,
                'primary_contact_id' => $primaryContactId,
                'secondary_contact_id' => $secondaryContactId,
                'logged_in_user_id' => null,
                'project_id' => $newProjectId,
                'lot_id' => $newLotId,
                'purchase_price' => $row->purchase_price ?? '0',
                'purchaser_type' => $this->decodeJson($row->purchaser_type),
                'trustee_name' => $row->trustee_name,
                'abn_acn' => $row->abn_acn,
                'SMSF_trust_setup' => $this->decodeJson($row->SMSF_trust_setup ?? null),
                'bare_trust_setup' => $this->decodeJson($row->bare_trust_setup ?? null),
                'funds_rollover' => $this->decodeJson($row->funds_rollover ?? null),
                'agree_lawlab' => $this->decodeJson($row->agree_lawlab ?? null),
                'firm' => $this->decodeJson($row->firm ?? null),
                'broker' => $this->decodeJson($row->broker ?? null),
                'finance_preapproval' => $row->finance_preapproval,
                'finance_days_req' => $row->finance_days_req ?? null,
                'deposit' => $row->deposit,
                'land_deposit' => $row->land_deposit ?? null,
                'build_deposit' => $row->build_deposit ?? null,
                'contract_send' => $this->decodeJson($row->contract_send ?? null),
                'agree' => $row->agree,
                'agree_date' => $row->agree_date,
                'family_trust' => $this->decodeJson($row->family_trust ?? null),
            ]);
            $count++;
        }
        $this->line('  Property reservations: '.$count.($skipped > 0 ? " (skipped {$skipped})" : ''));
    }

    private function importPropertyEnquiries(string $connection): void
    {
        $this->info('Importing property_enquiries...');
        $rows = DB::connection($connection)->table('property_enquiries')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $clientContactId = $this->contactId((int) $row->client_id);
            $agentContactId = $this->contactId((int) $row->agent_id);
            if ($clientContactId === null || $agentContactId === null) {
                $skipped++;
                continue;
            }
            PropertyEnquiry::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'client_contact_id' => $clientContactId,
                'agent_contact_id' => $agentContactId,
                'logged_in_user_id' => null,
                'purchaser_type' => $this->decodeJson($row->purchaser_type),
                'max_capacity' => $row->max_capacity,
                'preferred_location' => $row->preferred_location,
                'preapproval' => (bool) $row->preapproval,
                'property' => $this->decodeJson($row->property),
                'requesting_info' => $this->decodeJson($row->requesting_info ?? null),
                'instructions' => $row->instructions,
                'inspection_person' => $row->inspection_person,
                'inspection_date' => $row->inspection_date,
                'inspection_time' => $row->inspection_time,
                'cash_purchase' => $row->cash_purchase ? (bool) $row->cash_purchase : null,
            ]);
            $count++;
        }
        $this->line('  Property enquiries: '.$count.($skipped > 0 ? " (skipped {$skipped})" : ''));
    }

    private function importPropertySearches(string $connection): void
    {
        $this->info('Importing property_searches...');
        $rows = DB::connection($connection)->table('property_searches')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $clientContactId = $this->contactId((int) $row->client_id);
            $agentContactId = $this->contactId((int) $row->agent_id);
            if ($clientContactId === null || $agentContactId === null) {
                $skipped++;
                continue;
            }
            PropertySearch::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'client_contact_id' => $clientContactId,
                'agent_contact_id' => $agentContactId,
                'logged_in_user_id' => null,
                'purchaser_type' => $this->decodeJson($row->purchaser_type),
                'property_type' => $this->decodeJson($row->property_type ?? null),
                'no_of_bedrooms' => $row->no_of_bedrooms ?? '',
                'no_of_bathrooms' => $row->no_of_bathrooms ?? '',
                'no_of_carspaces' => $row->no_of_carspaces ?? '',
                'property_config_other' => $row->property_config_other,
                'max_capacity' => $row->max_capacity ?? '',
                'build_status' => $this->decodeJson($row->build_status ?? null),
                'preferred_location' => $row->preferred_location ?? '',
                'preapproval' => (bool) $row->preapproval,
                'lvr' => $row->lvr,
                'lender' => $row->lender,
                'extra_instructions' => $row->extra_instructions,
                'finance' => $row->finance,
                'purchase_type' => $row->purchase_type,
            ]);
            $count++;
        }
        $this->line('  Property searches: '.$count.($skipped > 0 ? " (skipped {$skipped})" : ''));
    }

    private function importSales(string $connection): void
    {
        $this->info('Importing sales...');
        $rows = DB::connection($connection)->table('sales')->whereNull('deleted_at')->orderBy('id')->get();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $clientContactId = $this->contactId((int) $row->client_id);
            $newLotId = $this->lotIdMap[(int) $row->lot_id] ?? null;
            $newProjectId = $this->projectIdMap[(int) $row->project_id] ?? null;
            if ($clientContactId === null || $newLotId === null || $newProjectId === null) {
                $skipped++;
                continue;
            }
            $sale = Sale::withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'client_contact_id' => $clientContactId,
                'lot_id' => $newLotId,
                'project_id' => $newProjectId,
                'developer_id' => $row->developer_id ? ($this->developerIdMap[(int) $row->developer_id] ?? null) : null,
                'comm_in_notes' => $row->comm_in_notes,
                'comm_out_notes' => $row->comm_out_notes,
                'payment_terms' => $row->payment_terms,
                'expected_commissions' => $this->decodeJson($row->expected_commissions ?? null),
                'finance_due_date' => $row->finance_due_date,
                'comms_in_total' => $row->comms_in_total,
                'comms_out_total' => $row->comms_out_total,
                'piab_comm' => $row->piab_comm,
                'affiliate_contact_id' => $row->affiliate_id ? $this->contactId((int) $row->affiliate_id) : null,
                'affiliate_comm' => $row->affiliate_comm,
                'subscriber_contact_id' => $row->subscriber_id ? $this->contactId((int) $row->subscriber_id) : null,
                'subscriber_comm' => $row->subscriber_comm,
                'sales_agent_contact_id' => $row->sales_agent_id ? $this->contactId((int) $row->sales_agent_id) : null,
                'sales_agent_comm' => $row->sales_agent_comm,
                'bdm_contact_id' => $row->bdm_id ? $this->contactId((int) $row->bdm_id) : null,
                'bdm_comm' => $row->bdm_comm,
                'referral_partner_contact_id' => $row->referral_partner_id ? $this->contactId((int) $row->referral_partner_id) : null,
                'referral_partner_comm' => $row->referral_partner_comm,
                'agent_contact_id' => $row->agent_id ? $this->contactId((int) $row->agent_id) : null,
                'agent_comm' => $row->agent_comm,
                'divide_percent' => $this->decodeJson($row->divide_percent ?? null),
                'is_comments_enabled' => (bool) $row->is_comments_enabled,
                'comments' => $row->comments,
                'is_sas_enabled' => (bool) $row->is_sas_enabled,
                'is_sas_max' => (bool) $row->is_sas_max,
                'sas_percent' => $row->sas_percent,
                'sas_fee' => $row->sas_fee,
                'summary_note' => $row->summary_note,
                'status_updated_at' => $row->status_updated_at,
                'custom_attributes' => $this->decodeJson($row->custom_attributes ?? null),
            ]);
            $this->saleIdMap[(int) $row->id] = $sale->id;
            $count++;
        }
        $this->line('  Sales: '.$count.($skipped > 0 ? " (skipped {$skipped})" : ''));
    }

    private function importCommissions(string $connection): void
    {
        $this->info('Importing commissions (Sale only)...');
        $saleType = 'App\\Models\\Sale';
        $rows = DB::connection($connection)->table('commissions')
            ->where('commissionable_type', $saleType)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
        $count = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $newSaleId = $this->saleIdMap[(int) $row->commissionable_id] ?? null;
            if ($newSaleId === null) {
                $skipped++;
                continue;
            }
            Commission::create([
                'commissionable_type' => Sale::class,
                'commissionable_id' => $newSaleId,
                'commission_in' => $row->commission_in,
                'commission_out' => $row->commission_out,
                'commission_profit' => $row->commission_profit,
                'commission_percent_in' => $row->commission_percent_in,
                'commission_percent_out' => $row->commission_percent_out,
                'commission_percent_profit' => $row->commission_percent_profit,
                'extra_attributes' => $this->decodeJson($row->extra_attributes ?? null),
            ]);
            $count++;
        }
        $this->line('  Commissions: '.$count.($skipped > 0 ? " (skipped {$skipped})" : ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode(is_string($value) ? $value : (string) $value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
