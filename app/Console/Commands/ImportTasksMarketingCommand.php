<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportTasksMarketingCommand extends Command
{
    protected $signature = 'fusion:import-tasks-relationships-marketing
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}
                            {--table=all : Which tables to import}';

    protected $description = 'Import tasks, relationships, partners, mail lists, campaign sites, and marketing data from MySQL legacy DB into PostgreSQL.';

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

        // Build lead_id -> contact_id map from contacts table
        $this->info('Building lead_id → contact_id map...');
        $leadMap = DB::table('contacts')
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();
        $this->info('Map built: '.count($leadMap).' entries.');

        $tables = ['tasks', 'relationships', 'partners', 'mail_lists', 'brochure_mail_jobs',
            'website_contacts', 'questionnaires', 'finance_assessments', 'notes',
            'comments', 'addresses', 'statuses', 'campaign_websites', 'campaign_website_templates',
            'campaign_website_project', 'resources', 'column_management', 'widget_settings',
            'advertisements', 'ai_bot_categories'];

        foreach ($tables as $t) {
            if ($table === 'all' || $table === $t) {
                $method = 'import'.str_replace('_', '', ucwords($t, '_'));
                if (method_exists($this, $method)) {
                    $overallFailed += $this->$method($dryRun, $chunk, $since, $force, $leadMap);
                }
            }
        }

        if ($overallFailed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function importTasks(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing tasks...');
        $failed = 0;
        $count = 0;

        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('tasks')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $assignedContactId = isset($row->assigned_id) ? ($leadMap[$row->assigned_id] ?? null) : null;
                        $attachedContactId = isset($row->attached_id) ? ($leadMap[$row->attached_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'assigned_contact_id' => $assignedContactId,
                            'attached_contact_id' => $attachedContactId,
                            'title' => $row->title ?? $row->name ?? 'Untitled',
                            'description' => $row->description ?? null,
                            'due_at' => $row->due_date ?? $row->due_at ?? null,
                            'priority' => $row->priority ?? 'medium',
                            'type' => $row->type ?? 'follow_up',
                            'status' => $row->status ?? 'pending',
                            'is_completed' => ! empty($row->is_completed),
                            'completed_at' => $row->completed_at ?? null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('tasks')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Task import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Tasks: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importRelationships(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing relationships...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('relationships')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $accountContactId = isset($row->account_id) ? ($leadMap[$row->account_id] ?? null) : null;
                        $relationContactId = isset($row->relation_id) ? ($leadMap[$row->relation_id] ?? null) : null;

                        if (! $accountContactId || ! $relationContactId) {
                            continue; // skip unmapped
                        }

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'account_contact_id' => $accountContactId,
                            'relation_contact_id' => $relationContactId,
                            'type' => $row->type ?? 'general',
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('relationships')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Relationship import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Relationships: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importPartners(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing partners...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('partners')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $contactId = isset($row->lead_id) ? ($leadMap[$row->lead_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'contact_id' => $contactId ?? 0,
                            'status' => $row->status ?? 'active',
                            'partner_type' => $row->partner_type ?? null,
                            'commission_rate' => $row->commission_rate ?? null,
                            'territory' => $row->territory ?? null,
                            'notes' => $row->notes ?? null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('partners')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Partner import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Partners: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importMailLists(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing mail lists...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('mail_lists')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'organization_id' => $defaultOrgId,
                            'name' => $row->name ?? 'Untitled',
                            'description' => $row->description ?? null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('mail_lists')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("MailList import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Mail lists: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importBrochureMailJobs(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing brochure mail jobs...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('brochure_mail_jobs')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $ownerContactId = isset($row->lead_id) ? ($leadMap[$row->lead_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'owner_contact_id' => $ownerContactId,
                            'status' => $row->status ?? 'pending',
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('brochure_mail_jobs')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("BrochureMailJob import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Brochure mail jobs: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importWebsiteContacts(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing website contacts...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('website_contacts')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $contactId = isset($row->lead_id) ? ($leadMap[$row->lead_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'contact_id' => $contactId,
                            'source' => $row->source ?? null,
                            'website_url' => $row->website_url ?? null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('website_contacts')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("WebsiteContact import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Website contacts: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importQuestionnaires(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing questionnaires...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        DB::connection('mysql_legacy')
            ->table('questionnaires')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $contactId = isset($row->lead_id) ? ($leadMap[$row->lead_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'contact_id' => $contactId,
                            'name' => $row->name ?? null,
                            'answers' => isset($row->answers) ? $row->answers : null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('questionnaires')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Questionnaire import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Questionnaires: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importFinanceAssessments(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing finance assessments...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        // Try to find the table; if missing in legacy DB, skip gracefully
        try {
            $hasTable = DB::connection('mysql_legacy')->getSchemaBuilder()->hasTable('finance_assessments');
        } catch (Throwable) {
            $hasTable = false;
        }

        if (! $hasTable) {
            $this->warn('finance_assessments table not found in legacy DB — skipping.');

            return 0;
        }

        DB::connection('mysql_legacy')
            ->table('finance_assessments')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $contactId = isset($row->lead_id) ? ($leadMap[$row->lead_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'contact_id' => $contactId,
                            'name' => $row->name ?? '',
                            'email' => $row->email ?? '',
                            'phone' => $row->phone ?? null,
                            'employment_type' => $row->employment_type ?? 'employed',
                            'annual_income' => $row->annual_income ?? null,
                            'other_income' => $row->other_income ?? null,
                            'existing_loans' => $row->existing_loans ?? null,
                            'credit_card_limit' => $row->credit_card_limit ?? null,
                            'deposit_available' => $row->deposit_available ?? null,
                            'property_purpose' => $row->property_purpose ?? null,
                            'preferred_loan_type' => $row->preferred_loan_type ?? null,
                            'notes' => $row->notes ?? null,
                            'submitted_at' => $row->submitted_at ?? $row->created_at ?? now(),
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('finance_assessments')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("FinanceAssessment import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Finance assessments: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importNotes(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing notes...');
        $failed = 0;
        $count = 0;

        DB::connection('mysql_legacy')
            ->table('notes')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count) {
                foreach ($rows as $row) {
                    try {
                        // Map noteable_type Lead → Contact
                        $noteableType = 'App\\Models\\Contact';
                        $noteableId = null;
                        if (isset($row->noteable_type, $row->noteable_id)) {
                            if (str_contains(mb_strtolower($row->noteable_type), 'lead')) {
                                $noteableId = $leadMap[$row->noteable_id] ?? null;
                            } else {
                                $noteableId = $row->noteable_id;
                                $noteableType = $row->noteable_type;
                            }
                        } elseif (isset($row->lead_id)) {
                            $noteableId = $leadMap[$row->lead_id] ?? null;
                        }

                        $data = [
                            'noteable_type' => $noteableType,
                            'noteable_id' => $noteableId,
                            'author_id' => $row->author_id ?? $row->user_id ?? null,
                            'content' => $row->content ?? $row->note ?? '',
                            'type' => $row->type ?? 'general',
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('crm_notes')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Note import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Notes: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importComments(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing comments...');
        $failed = 0;
        $count = 0;

        DB::connection('mysql_legacy')
            ->table('comments')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$failed, &$count) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'commentable_type' => $row->commentable_type ?? null,
                            'commentable_id' => $row->commentable_id ?? null,
                            'user_id' => $row->user_id ?? null,
                            'comment' => $row->comment ?? '',
                            'is_approved' => ! empty($row->is_approved),
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('crm_comments')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Comment import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Comments: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importAddresses(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing addresses...');
        $failed = 0;
        $count = 0;

        DB::connection('mysql_legacy')
            ->table('addresses')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $leadMap, &$failed, &$count) {
                foreach ($rows as $row) {
                    try {
                        // Map addressable Lead → Contact
                        $addressableType = 'App\\Models\\Contact';
                        $addressableId = null;
                        if (isset($row->addressable_type, $row->addressable_id)) {
                            if (str_contains(mb_strtolower($row->addressable_type), 'lead')) {
                                $addressableId = $leadMap[$row->addressable_id] ?? null;
                            } else {
                                $addressableId = $row->addressable_id;
                                $addressableType = $row->addressable_type;
                            }
                        }

                        $data = [
                            'addressable_type' => $addressableType,
                            'addressable_id' => $addressableId,
                            'type' => $row->type ?? 'home',
                            'line1' => $row->line1 ?? $row->address_line1 ?? null,
                            'line2' => $row->line2 ?? $row->address_line2 ?? null,
                            'city' => $row->city ?? null,
                            'state' => $row->state ?? null,
                            'postcode' => $row->postcode ?? $row->postal_code ?? null,
                            'country' => $row->country ?? 'AU',
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('crm_addresses')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Address import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Addresses: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importStatuses(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing statuses...');
        $failed = 0;
        $count = 0;

        try {
            $hasTable = DB::connection('mysql_legacy')->getSchemaBuilder()->hasTable('statuses');
        } catch (Throwable) {
            $hasTable = false;
        }
        if (! $hasTable) {
            $this->warn('statuses table not found in legacy DB — skipping.');

            return 0;
        }

        DB::connection('mysql_legacy')
            ->table('statuses')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$failed, &$count) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'name' => $row->name ?? '',
                            'color' => $row->color ?? null,
                            'type' => $row->type ?? null,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('crm_statuses')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("Status import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Statuses: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importCampaignWebsites(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing campaign website templates...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        // First import templates
        try {
            DB::connection('mysql_legacy')
                ->table('campaign_website_templates')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$failed, &$count) {
                    foreach ($rows as $row) {
                        try {
                            $data = [
                                'name' => $row->name ?? 'Template',
                                'template_code' => $row->template_code ?? $row->code ?? null,
                                'description' => $row->description ?? null,
                                'legacy_id' => $row->id,
                                'created_at' => $row->created_at ?? now(),
                                'updated_at' => $row->updated_at ?? now(),
                            ];
                            if (! $dryRun) {
                                DB::table('campaign_website_templates')->updateOrInsert(['legacy_id' => $row->id], $data);
                            }
                            $count++;
                        } catch (Throwable $e) {
                            Log::error('CampaignWebsiteTemplate import failed: '.$e->getMessage());
                            $failed++;
                        }
                    }
                });
        } catch (Throwable $e) {
            $this->warn('campaign_website_templates not found in legacy DB — skipping.');
        }

        $this->info("Campaign website templates: imported {$count}, failed {$failed}");

        // Build template legacy_id map
        $templateMap = DB::table('campaign_website_templates')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        $this->info('Importing campaign websites...');
        $count = 0;

        DB::connection('mysql_legacy')
            ->table('campaign_websites')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, $templateMap, &$failed, &$count, $defaultOrgId) {
                foreach ($rows as $row) {
                    try {
                        $templateId = isset($row->campaign_website_template_id) ? ($templateMap[$row->campaign_website_template_id] ?? null) : null;

                        $data = [
                            'organization_id' => $defaultOrgId,
                            'campaign_website_template_id' => $templateId,
                            'site_id' => $row->site_id ?? (string) \Illuminate\Support\Str::uuid(),
                            'title' => $row->title ?? 'Campaign Site',
                            'short_link' => $row->short_link ?? null,
                            'is_multiple_property' => ! empty($row->is_multiple_property),
                            'primary_color' => $row->primary_color ?? null,
                            'secondary_color' => $row->secondary_color ?? null,
                            'header' => isset($row->header) ? $row->header : null,
                            'banner' => isset($row->banner) ? $row->banner : null,
                            'page_content' => isset($row->page_content) ? $row->page_content : null,
                            'footer' => isset($row->footer) ? $row->footer : null,
                            'puck_content' => null,
                            'puck_enabled' => false,
                            'legacy_id' => $row->id,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];

                        if (! $dryRun) {
                            DB::table('campaign_websites')->updateOrInsert(['legacy_id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        Log::error("CampaignWebsite import failed for legacy id {$row->id}: ".$e->getMessage());
                        $failed++;
                    }
                }
            });

        $this->info("Campaign websites: imported {$count}, failed {$failed}");

        // Import campaign_website_project pivot
        try {
            $this->info('Importing campaign_website_project pivot...');
            $cwMap = DB::table('campaign_websites')
                ->whereNotNull('legacy_id')
                ->pluck('id', 'legacy_id')
                ->all();
            $projectMap = DB::table('projects')
                ->whereNotNull('legacy_id')
                ->pluck('id', 'legacy_id')
                ->all();

            $pivotCount = 0;
            DB::connection('mysql_legacy')
                ->table('campaign_website_project')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, $cwMap, $projectMap, &$failed, &$pivotCount) {
                    foreach ($rows as $row) {
                        try {
                            $cwId = isset($row->campaign_website_id) ? ($cwMap[$row->campaign_website_id] ?? null) : null;
                            $projectId = isset($row->project_id) ? ($projectMap[$row->project_id] ?? null) : null;
                            if (! $cwId || ! $projectId) {
                                continue;
                            }
                            if (! $dryRun) {
                                DB::table('campaign_website_project')
                                    ->updateOrInsert(
                                        ['campaign_website_id' => $cwId, 'project_id' => $projectId],
                                        ['created_at' => now(), 'updated_at' => now()]
                                    );
                            }
                            $pivotCount++;
                        } catch (Throwable $e) {
                            Log::error('CampaignWebsiteProject pivot import failed: '.$e->getMessage());
                            $failed++;
                        }
                    }
                });
            $this->info("Campaign website project pivot: imported {$pivotCount}");
        } catch (Throwable $e) {
            $this->warn('campaign_website_project import error: '.$e->getMessage());
        }

        return $failed;
    }

    private function importCampaignWebsiteTemplates(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        // Handled inside importCampaignWebsites
        return 0;
    }

    private function importCampaignWebsiteProject(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        // Handled inside importCampaignWebsites
        return 0;
    }

    private function importResources(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing resources...');
        $failed = 0;
        $count = 0;
        $defaultOrgId = DB::table('organizations')->value('id') ?? 1;

        try {
            // Resource groups
            DB::connection('mysql_legacy')
                ->table('resource_groups')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                    foreach ($rows as $row) {
                        try {
                            $data = ['name' => $row->name ?? '', 'legacy_id' => $row->id, 'created_at' => now(), 'updated_at' => now()];
                            if (! $dryRun) {
                                DB::table('resource_groups')->updateOrInsert(['legacy_id' => $row->id], $data);
                            }
                            $count++;
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }
                });

            // Resources
            DB::connection('mysql_legacy')
                ->table('resources')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                    foreach ($rows as $row) {
                        try {
                            $data = [
                                'title' => $row->title ?? $row->name ?? '',
                                'description' => $row->description ?? null,
                                'file_path' => $row->file_path ?? null,
                                'url' => $row->url ?? null,
                                'type' => $row->type ?? null,
                                'legacy_id' => $row->id,
                                'created_at' => $row->created_at ?? now(),
                                'updated_at' => $row->updated_at ?? now(),
                            ];
                            if (! $dryRun) {
                                DB::table('crm_resources')->updateOrInsert(['legacy_id' => $row->id], $data);
                            }
                            $count++;
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }
                });
        } catch (Throwable $e) {
            $this->warn('Resources import error: '.$e->getMessage());
        }

        $this->info("Resources: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importColumnManagement(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing column management...');
        $failed = 0;
        $count = 0;

        try {
            DB::connection('mysql_legacy')
                ->table('column_management')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                    foreach ($rows as $row) {
                        try {
                            $userId = DB::table('users')->where('id', $row->user_id)->value('id');
                            if (! $userId) {
                                return;
                            }
                            $data = [
                                'user_id' => $userId,
                                'table_name' => $row->table_name ?? '',
                                'columns' => $row->columns ?? '{}',
                                'legacy_id' => $row->id,
                                'created_at' => $row->created_at ?? now(),
                                'updated_at' => $row->updated_at ?? now(),
                            ];
                            if (! $dryRun) {
                                DB::table('column_management')->updateOrInsert(
                                    ['user_id' => $userId, 'table_name' => $data['table_name']],
                                    $data
                                );
                            }
                            $count++;
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }
                });
        } catch (Throwable $e) {
            $this->warn('Column management import error: '.$e->getMessage());
        }

        $this->info("Column management: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importWidgetSettings(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing widget settings...');
        $failed = 0;
        $count = 0;

        try {
            DB::connection('mysql_legacy')
                ->table('widget_settings')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                    foreach ($rows as $row) {
                        try {
                            $userId = DB::table('users')->where('id', $row->user_id)->value('id');
                            if (! $userId) {
                                return;
                            }
                            $data = [
                                'user_id' => $userId,
                                'widget_name' => $row->widget_name ?? '',
                                'settings' => $row->settings ?? '{}',
                                'legacy_id' => $row->id,
                                'created_at' => $row->created_at ?? now(),
                                'updated_at' => $row->updated_at ?? now(),
                            ];
                            if (! $dryRun) {
                                DB::table('widget_settings')->updateOrInsert(
                                    ['user_id' => $userId, 'widget_name' => $data['widget_name']],
                                    $data
                                );
                            }
                            $count++;
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }
                });
        } catch (Throwable $e) {
            $this->warn('Widget settings import error: '.$e->getMessage());
        }

        $this->info("Widget settings: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importAdvertisements(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing advertisements...');
        $failed = 0;
        $count = 0;

        try {
            DB::connection('mysql_legacy')
                ->table('ad_managements')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                    foreach ($rows as $row) {
                        try {
                            $data = [
                                'title' => $row->title ?? '',
                                'image_path' => $row->image_path ?? $row->image ?? null,
                                'link_url' => $row->link_url ?? $row->url ?? null,
                                'status' => $row->status ?? 'active',
                                'display_order' => $row->display_order ?? 0,
                                'legacy_id' => $row->id,
                                'created_at' => $row->created_at ?? now(),
                                'updated_at' => $row->updated_at ?? now(),
                            ];
                            if (! $dryRun) {
                                DB::table('advertisements')->updateOrInsert(['legacy_id' => $row->id], $data);
                            }
                            $count++;
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }
                });
        } catch (Throwable $e) {
            $this->warn('Advertisements (ad_managements) import error: '.$e->getMessage());
        }

        $this->info("Advertisements: imported {$count}, failed {$failed}");

        return $failed;
    }

    private function importAiBotCategories(bool $dryRun, int $chunk, ?string $since, bool $force, array $leadMap): int
    {
        $this->info('Importing AI bot categories...');
        $failed = 0;
        $count = 0;

        try {
            $hasTable = DB::connection('mysql_legacy')->getSchemaBuilder()->hasTable('ai_bot_categories');
        } catch (Throwable) {
            $hasTable = false;
        }
        if (! $hasTable) {
            $this->warn('ai_bot_categories not found in legacy DB — skipping.');

            return 0;
        }

        DB::connection('mysql_legacy')
            ->table('ai_bot_categories')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($dryRun, &$count, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $data = [
                            'name' => $row->name ?? '',
                            'slug' => \Illuminate\Support\Str::slug($row->name ?? 'category-'.$row->id),
                            'icon' => $row->icon ?? null,
                            'display_order' => $row->display_order ?? 0,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];
                        if (! $dryRun) {
                            DB::table('ai_bot_categories')->updateOrInsert(['id' => $row->id], $data);
                        }
                        $count++;
                    } catch (Throwable $e) {
                        $failed++;
                    }
                }
            });

        $this->info("AI bot categories: imported {$count}, failed {$failed}");

        return $failed;
    }
}
