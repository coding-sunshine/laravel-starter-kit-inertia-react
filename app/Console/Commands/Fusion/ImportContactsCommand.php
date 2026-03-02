<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\Scopes\OrganizationScope;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportContactsCommand extends Command
{
    protected $signature = 'fusion:import-contacts
                            {--force : Run even if legacy connection fails}
                            {--fresh : Truncate contacts/contact_emails/contact_phones/sources/companies first}
                            {--chunk=200 : Chunk size for leads (smaller = less memory)}
                            {--from-id= : Only process leads with id >= this (e.g. 6906 to finish after a stall)}
                            {--to-id= : Only process leads with id <= this (combine with --from-id for a small batch)}
                            {--skip-details : Skip contact_emails/contact_phones; run again without this to sync them}
                            {--details-only : Only sync contact_emails/contact_phones from legacy (contacts must already be imported)}';

    protected $description = 'Import contacts, contact_emails, contact_phones from MySQL legacy (leads + polymorphic contacts). Idempotent: run again without --fresh to resume if interrupted.';

    /** @var array<int, int> old source id => new source id */
    private array $sourceIdMap = [];

    /** @var array<int, int> old company id => new company id */
    private array $companyIdMap = [];

    /** @var array<int, int> old lead id => new contact id */
    private array $leadToContactIdMap = [];

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Legacy MySQL connection failed: '.$e->getMessage());
            $this->line('Set MYSQL_LEGACY_HOST, MYSQL_LEGACY_DATABASE, MYSQL_LEGACY_USERNAME, MYSQL_LEGACY_PASSWORD in .env (after restoring fv3_2026-03-02.sql to MySQL).');
            if (! $this->option('force')) {
                return self::FAILURE;
            }
        }

        if ($this->option('details-only')) {
            $this->importPolymorphicContactDetails($connection);
            $this->info('Details import complete. Counts: Emails='.ContactEmail::count().', Phones='.ContactPhone::count());

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->truncateFusionTables();
        } else {
            $existing = Contact::withoutGlobalScope(OrganizationScope::class)->whereNotNull('legacy_lead_id')->count();
            if ($existing > 0) {
                $this->info("Resuming: {$existing} contacts already imported. Missing leads will be added, then emails/phones will sync.");
            }
        }

        $this->importSources($connection);
        $this->importCompanies($connection);
        $this->importLeadsAsContacts($connection);
        if (! $this->option('skip-details')) {
            $this->importPolymorphicContactDetails($connection);
        } else {
            $this->warn('Skipped contact_emails/contact_phones (--skip-details). Run without it to sync details.');
        }

        $this->info('Import complete. Lead→Contact map: contacts.legacy_lead_id. Counts: Contacts='.Contact::withoutGlobalScope(OrganizationScope::class)->count().', Emails='.ContactEmail::count().', Phones='.ContactPhone::count());

        return self::SUCCESS;
    }

    private function truncateFusionTables(): void
    {
        $this->info('Truncating fusion tables (fresh import)...');
        DB::table('contact_emails')->delete();
        DB::table('contact_phones')->delete();
        DB::table('contacts')->delete();
        DB::table('companies')->delete();
        DB::table('sources')->delete();
        $this->sourceIdMap = [];
        $this->companyIdMap = [];
        $this->leadToContactIdMap = [];
    }

    private function importSources(string $connection): void
    {
        $this->info('Importing sources...');
        $rows = DB::connection($connection)->table('sources')->orderBy('id')->get();
        foreach ($rows as $row) {
            if ($row->deleted_at !== null) {
                continue;
            }
            $s = Source::firstOrCreate(
                ['legacy_source_id' => (int) $row->id],
                [
                    'organization_id' => null,
                    'label' => $row->label,
                    'description' => $row->description,
                    'old_table_id' => $row->old_table_id,
                ]
            );
            $this->sourceIdMap[(int) $row->id] = $s->id;
        }
        $this->line('  Sources: '.count($this->sourceIdMap));
    }

    private function importCompanies(string $connection): void
    {
        $this->info('Importing companies...');
        $rows = DB::connection($connection)->table('companies')->orderBy('id')->get();
        foreach ($rows as $row) {
            if ($row->deleted_at !== null) {
                continue;
            }
            $c = Company::firstOrCreate(
                ['legacy_company_id' => (int) $row->id],
                [
                    'organization_id' => null,
                    'name' => $row->name,
                    'bk_name' => $row->bk_name ?? null,
                    'slogan' => $row->slogan ?? null,
                    'extra_company_info' => $row->extra_company_info ? (is_string($row->extra_company_info) ? json_decode($row->extra_company_info, true) : $row->extra_company_info) : null,
                ]
            );
            $this->companyIdMap[(int) $row->id] = $c->id;
        }
        $this->line('  Companies: '.count($this->companyIdMap));
    }

    private function importLeadsAsContacts(string $connection): void
    {
        $this->info('Importing leads as contacts...');
        $chunkSize = (int) $this->option('chunk');
        $query = DB::connection($connection)->table('leads')->whereNull('deleted_at');
        $fromId = $this->option('from-id');
        $toId = $this->option('to-id');
        if ($fromId !== null && $fromId !== '') {
            $query->where('id', '>=', (int) $fromId);
            $this->line('  From lead id: '.(int) $fromId);
        }
        if ($toId !== null && $toId !== '') {
            $query->where('id', '<=', (int) $toId);
            $this->line('  To lead id: '.(int) $toId);
        }
        $total = (int) $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Use chunkById to avoid slow OFFSET queries (stays fast at 40%, 80%, etc.)
        DB::connection($connection)->table('leads')->whereNull('deleted_at')
            ->when($fromId !== null && $fromId !== '', fn ($q) => $q->where('id', '>=', (int) $fromId))
            ->when($toId !== null && $toId !== '', fn ($q) => $q->where('id', '<=', (int) $toId))
            ->orderBy('id')->chunkById($chunkSize, function ($leads) use ($bar): void {
                foreach ($leads as $lead) {
                    $type = $this->deriveContactType($lead);
                    $contact = Contact::withoutGlobalScope(OrganizationScope::class)->updateOrCreate(
                        ['legacy_lead_id' => (int) $lead->id],
                        [
                            'organization_id' => null,
                            'first_name' => $lead->first_name ?? '',
                            'last_name' => $lead->last_name,
                            'job_title' => $lead->job_title,
                            'type' => $type,
                            'stage' => $lead->stage,
                            'source_id' => $lead->source_id ? ($this->sourceIdMap[(int) $lead->source_id] ?? null) : null,
                            'company_id' => $lead->company_id ? ($this->companyIdMap[(int) $lead->company_id] ?? null) : null,
                            'company_name' => null,
                            'extra_attributes' => $lead->extra_attributes ? (is_string($lead->extra_attributes) ? json_decode($lead->extra_attributes, true) : $lead->extra_attributes) : null,
                            'last_followup_at' => $lead->last_followup_at,
                            'next_followup_at' => $lead->next_followup_at,
                            'created_by' => null,
                            'updated_by' => null,
                        ]
                    );
                    $this->leadToContactIdMap[(int) $lead->id] = $contact->id;
                    $bar->advance();
                }
                unset($leads);
                if (function_exists('gc_mem_caches')) {
                    gc_mem_caches();
                }
            }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->line('  Contacts: '.count($this->leadToContactIdMap));
    }

    private function deriveContactType(object $lead): string
    {
        if (! empty($lead->is_partner)) {
            return 'partner';
        }
        $extra = $lead->extra_attributes ? (is_string($lead->extra_attributes) ? json_decode($lead->extra_attributes, true) : $lead->extra_attributes) : null;
        if (is_array($extra)) {
            if (! empty($extra['referral_partner'])) {
                return 'partner';
            }
            if (! empty($extra['sales_agent'])) {
                return 'agent';
            }
            if (! empty($extra['bdm'])) {
                return 'bdm';
            }
            if (! empty($extra['affiliate'])) {
                return 'affiliate';
            }
            if (! empty($extra['subscriber'])) {
                return 'subscriber';
            }
        }

        return 'lead';
    }

    private function importPolymorphicContactDetails(string $connection): void
    {
        $this->info('Importing polymorphic contact details (emails/phones)...');
        // Rebuild map from DB (in case we're resuming) and clear existing detail rows for imported contacts
        $this->leadToContactIdMap = Contact::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();
        $contactIds = array_values($this->leadToContactIdMap);
        if ($contactIds !== []) {
            // Delete in batches to avoid huge IN clause
            foreach (array_chunk($contactIds, 2000) as $chunk) {
                ContactEmail::whereIn('contact_id', $chunk)->delete();
                ContactPhone::whereIn('contact_id', $chunk)->delete();
            }
        }

        $leadType = 'App\\Models\\Lead';
        $emails = 0;
        $phones = 0;
        $chunkSize = (int) $this->option('chunk');
        $total = (int) DB::connection($connection)->table('contacts')->where('model_type', $leadType)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection($connection)->table('contacts')
            ->where('model_type', $leadType)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$emails, &$phones, $bar): void {
                foreach ($rows as $row) {
                    $newContactId = $this->leadToContactIdMap[(int) $row->model_id] ?? null;
                    if ($newContactId === null) {
                        $bar->advance();

                        continue;
                    }
                    $type = $row->type ?? '';
                    $value = $row->value ?? '';
                    if ($value === '') {
                        $bar->advance();

                        continue;
                    }
                    $customAttributes = $row->custom_attributes ? (is_string($row->custom_attributes) ? json_decode($row->custom_attributes, true) : $row->custom_attributes) : null;
                    $subType = is_array($customAttributes) && isset($customAttributes['sub_type']) ? $customAttributes['sub_type'] : null;

                    if (str_starts_with(mb_strtolower($type), 'email') || $type === 'email') {
                        ContactEmail::create([
                            'contact_id' => $newContactId,
                            'type' => $type,
                            'value' => $value,
                            'is_primary' => $type === 'email_1',
                            'order_column' => $row->order_column,
                        ]);
                        $emails++;
                    } elseif (mb_strtolower($type) === 'phone' || ($subType && (mb_stripos($subType, 'phone') !== false || mb_stripos($subType, 'mobile') !== false || mb_stripos($subType, 'fax') !== false))) {
                        ContactPhone::create([
                            'contact_id' => $newContactId,
                            'type' => $subType ?? $type,
                            'value' => $value,
                            'is_primary' => ($subType ?? $type) === 'phone 1' || ($subType ?? $type) === 'mobile',
                            'order_column' => $row->order_column,
                        ]);
                        $phones++;
                    }
                    // Skip non-email, non-phone types (googleplus, skype, linkedin, facebook, twitter, website) per Step 1 scope
                    $bar->advance();
                }
                unset($rows);
                if (function_exists('gc_mem_caches')) {
                    gc_mem_caches();
                }
            }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->line('  Contact emails: '.$emails.', contact phones: '.$phones);
    }
}
