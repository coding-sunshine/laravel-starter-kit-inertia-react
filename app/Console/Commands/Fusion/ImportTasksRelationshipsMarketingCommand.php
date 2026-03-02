<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\AdManagement;
use App\Models\Address;
use App\Models\BrochureMailJob;
use App\Models\CampaignWebsite;
use App\Models\CampaignWebsiteTemplate;
use App\Models\ColumnManagement;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\FinanceAssessment;
use App\Models\MailJobStatus;
use App\Models\MailList;
use App\Models\Note;
use App\Models\OnlineformContact;
use App\Models\Partner;
use App\Models\Questionnaire;
use App\Models\Relationship;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceGroup;
use App\Models\Scopes\OrganizationScope;
use App\Models\Status;
use App\Models\SurveyQuestion;
use App\Models\Task;
use App\Models\WebsiteContact;
use App\Models\WidgetSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportTasksRelationshipsMarketingCommand extends Command
{
    protected $signature = 'fusion:import-tasks-relationships-marketing
                            {--force : Run even if legacy connection fails}
                            {--fresh : Truncate Step 5 tables first}
                            {--chunk=1000 : Chunk size for large tables}
                            {--organization-id= : Organization ID to assign}';

    protected $description = 'Import tasks, relationships, partners, mail_lists, brochure_mail_jobs, website_contacts, questionnaires, finance_assessments, notes, comments, tags/taggables, addresses, statuses/statusables, onlineform_contacts, campaign_websites, resources, ad_managements, column_management, widget_settings, survey_questions from MySQL legacy.';

    private ?int $organizationId = null;

    /** @var array<int, int> */
    private array $leadToContactId = [];

    /** @var array<int, int> */
    private array $userIdMap = [];

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

        $this->userIdMap = DB::table('users')
            ->pluck('id', 'id')
            ->all();

        if ($this->leadToContactId === []) {
            $this->warn('No contacts with legacy_lead_id found. Run fusion:import-contacts first (Step 1).');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncateTables();
        }

        $chunk = (int) $this->option('chunk');

        $this->importTasks($connection, $chunk);
        $this->importRelationships($connection, $chunk);
        $this->importPartners($connection, $chunk);
        $this->importMailLists($connection, $chunk);
        $this->importWebsiteContacts($connection, $chunk);
        $this->importQuestionnaires($connection, $chunk);
        $this->importFinanceAssessments($connection, $chunk);
        $this->importNotes($connection, $chunk);
        $this->importComments($connection, $chunk);
        $this->importAddresses($connection, $chunk);
        $this->importStatuses($connection, $chunk);
        $this->importOnlineformContacts($connection, $chunk);
        $this->importCampaignWebsites($connection, $chunk);
        $this->importResources($connection, $chunk);
        $this->importAdAndLayout($connection, $chunk);

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function truncateTables(): void
    {
        $this->info('Truncating Step 5 tables...');

        foreach ([
            'survey_questions',
            'widget_settings',
            'column_management',
            'ad_managements',
            'resource_categories',
            'resource_groups',
            'resources',
            'campaign_website_project',
            'campaign_website_templates',
            'campaign_websites',
            'onlineform_contacts',
            'statusables',
            'statuses',
            'addresses',
            'comments',
            'notes',
            'finance_assessments',
            'questionnaires',
            'website_contacts',
            'brochure_mail_jobs',
            'mail_job_statuses',
            'mail_lists',
            'partners',
            'relationships',
            'tasks',
        ] as $table) {
            DB::table($table)->delete();
        }
    }

    private function contactId(?int $legacyLeadId): ?int
    {
        if ($legacyLeadId === null) {
            return null;
        }

        return $this->leadToContactId[$legacyLeadId] ?? null;
    }

    private function importTasks(string $connection, int $chunk): void
    {
        $this->info('Importing tasks...');

        DB::connection($connection)
            ->table('tasks')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $assignedContactId = $this->contactId($row->assigned_id !== null ? (int) $row->assigned_id : null);
                    $attachedContactId = $this->contactId($row->attached_id !== null ? (int) $row->attached_id : null);

                    Task::create([
                        'organization_id' => $this->organizationId,
                        'assigned_contact_id' => $assignedContactId,
                        'attached_contact_id' => $attachedContactId,
                        'assigned_to_user_id' => null,
                        'title' => (string) ($row->title ?? 'Task'),
                        'description' => $row->description ?? null,
                        'due_at' => property_exists($row, 'due_at') ? $row->due_at : null,
                        'priority' => property_exists($row, 'priority') ? $row->priority : null,
                        'status' => property_exists($row, 'status') ? $row->status : null,
                        'completed_at' => property_exists($row, 'completed_at') ? $row->completed_at : null,
                    ]);
                }
            });
    }

    private function importRelationships(string $connection, int $chunk): void
    {
        $this->info('Importing relationships...');

        DB::connection($connection)
            ->table('relationships')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $accountContactId = $this->contactId((int) $row->account_id);
                    $relationContactId = $this->contactId((int) $row->relation_id);

                    if ($accountContactId === null || $relationContactId === null) {
                        continue;
                    }

                    Relationship::create([
                        'organization_id' => $this->organizationId,
                        'account_contact_id' => $accountContactId,
                        'relation_contact_id' => $relationContactId,
                        'type' => property_exists($row, 'type') ? $row->type : null,
                    ]);
                }
            });
    }

    private function importPartners(string $connection, int $chunk): void
    {
        $this->info('Importing partners...');

        DB::connection($connection)
            ->table('partners')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $contactId = $this->contactId((int) $row->lead_id);
                    if ($contactId === null) {
                        continue;
                    }

                    Partner::create([
                        'organization_id' => $this->organizationId,
                        'contact_id' => $contactId,
                        'role' => $row->role ?? null,
                        'status' => $row->status ?? null,
                        'extra_attributes' => null,
                    ]);
                }
            });
    }

    private function importMailLists(string $connection, int $chunk): void
    {
        $this->info('Importing mail_lists and brochure_mail_jobs...');

        DB::connection($connection)
            ->table('mail_lists')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $ownerContactId = $this->contactId((int) $row->lead_id);

                    $clientIds = $this->mapLeadIdJsonToContactIds($row->client_ids ?? null);

                    MailList::create([
                        'organization_id' => $this->organizationId,
                        'owner_contact_id' => $ownerContactId,
                        'name' => (string) $row->name,
                        'client_ids' => $clientIds,
                    ]);
                }
            });

        // seed mail_job_statuses if empty
        if (MailJobStatus::count() === 0) {
            foreach ([
                ['name' => 'Pending', 'code' => 'pending'],
                ['name' => 'Processing', 'code' => 'processing'],
                ['name' => 'Completed', 'code' => 'completed'],
                ['name' => 'Failed', 'code' => 'failed'],
            ] as $status) {
                MailJobStatus::firstOrCreate(['code' => $status['code']], $status);
            }
        }

        DB::connection($connection)
            ->table('brochure_mail_jobs')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $ownerContactId = $this->contactId((int) $row->lead_id);
                    $clientContactIds = $this->mapLeadIdJsonToContactIds($row->client_ids ?? null);

                    BrochureMailJob::create([
                        'organization_id' => $this->organizationId,
                        'owner_contact_id' => $ownerContactId,
                        'mail_list_id' => null,
                        'status_id' => MailJobStatus::where('code', 'pending')->value('id'),
                        'scheduled_at' => property_exists($row, 'scheduled_at') ? $row->scheduled_at : null,
                        'client_contact_ids' => $clientContactIds,
                        'name' => $row->name ?? null,
                    ]);
                }
            });
    }

    private function importWebsiteContacts(string $connection, int $chunk): void
    {
        $this->info('Importing website_contacts...');

        DB::connection($connection)
            ->table('website_contacts')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $contactId = $this->contactId((int) $row->lead_id);

                    WebsiteContact::create([
                        'organization_id' => $this->organizationId,
                        'contact_id' => $contactId,
                        'source' => isset($row->source) ? $row->source : null,
                        'payload' => $this->decodeJson($row->payload ?? null),
                    ]);
                }
            });
    }

    private function importQuestionnaires(string $connection, int $chunk): void
    {
        $this->info('Importing questionnaires and survey_questions...');

        DB::connection($connection)
            ->table('questionnaires')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $contactId = $this->contactId((int) $row->lead_id);

                    $questionnaire = Questionnaire::create([
                        'organization_id' => $this->organizationId,
                        'contact_id' => $contactId,
                        'title' => $row->title ?? 'Questionnaire',
                        'answers' => $this->decodeJson($row->answers ?? null),
                    ]);

                    // optional: no detailed mapping of question rows here; handled in survey_questions import
                }
            });

        DB::connection($connection)
            ->table('survey_questions')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    SurveyQuestion::create([
                        'questionnaire_id' => isset($row->questionnaire_id) ? $row->questionnaire_id : null,
                        'question' => (string) $row->question,
                        'type' => $row->type ?? 'text',
                        'options' => $this->decodeJson($row->options ?? null),
                    ]);
                }
            });
    }

    private function importFinanceAssessments(string $connection, int $chunk): void
    {
        $this->info('Importing finance_assessments...');

        DB::connection($connection)
            ->table('finance_assessments')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $agentContactId = $this->contactId((int) $row->loggedin_agent_id);

                    FinanceAssessment::create([
                        'organization_id' => $this->organizationId,
                        'agent_contact_id' => $agentContactId,
                        'logged_in_user_id' => null,
                        'data' => $this->decodeJson($row->data ?? null),
                    ]);
                }
            });
    }

    private function importNotes(string $connection, int $chunk): void
    {
        $this->info('Importing notes...');

        DB::connection($connection)
            ->table('notes')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $noteableType = (string) $row->noteable_type;
                    $noteableId = (int) $row->noteable_id;

                    if ($noteableType === 'App\\Models\\Lead') {
                        $contactId = $this->contactId($noteableId);
                        if ($contactId === null) {
                            continue;
                        }
                        $noteableType = Contact::class;
                        $noteableId = $contactId;
                    }

                    $authorId = isset($row->author_id) ? (int) $row->author_id : null;
                    // If the author user no longer exists, drop the FK to avoid violations.
                    if ($authorId !== null && ! array_key_exists($authorId, $this->userIdMap)) {
                        $authorId = null;
                    }

                    Note::create([
                        'organization_id' => $this->organizationId,
                        'noteable_type' => $noteableType,
                        'noteable_id' => $noteableId,
                        'author_id' => $authorId,
                        'content' => (string) $row->content,
                        'type' => $row->type,
                    ]);
                }
            });
    }

    private function importComments(string $connection, int $chunk): void
    {
        $this->info('Importing comments...');

        DB::connection($connection)
            ->table('comments')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $commentableType = (string) $row->commentable_type;
                    $commentableId = (int) $row->commentable_id;

                    if ($commentableType === 'App\\Models\\Lead') {
                        $contactId = $this->contactId($commentableId);
                        if ($contactId === null) {
                            continue;
                        }
                        $commentableType = Contact::class;
                        $commentableId = $contactId;
                    }

                    $userId = isset($row->user_id) ? (int) $row->user_id : null;
                    if ($userId !== null && ! array_key_exists($userId, $this->userIdMap)) {
                        $userId = null;
                    }

                    Comment::create([
                        'commentable_type' => $commentableType,
                        'commentable_id' => $commentableId,
                        'user_id' => $userId,
                        'comment' => (string) $row->comment,
                        'is_approved' => (bool) ($row->is_approved ?? true),
                    ]);
                }
            });
    }

    private function importAddresses(string $connection, int $chunk): void
    {
        $this->info('Importing addresses...');

        DB::connection($connection)
            ->table('addresses')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $addressableType = (string) $row->model_type;
                    $addressableId = (int) $row->model_id;

                    if ($addressableType === 'App\\Models\\Lead') {
                        $contactId = $this->contactId($addressableId);
                        if ($contactId === null) {
                            continue;
                        }
                        $addressableType = Contact::class;
                        $addressableId = $contactId;
                    }

                    // Map legacy address structure to new structure
                    $line1 = trim(($row->house_number ?? '') . ' ' . ($row->street ?? ''));
                    $line2 = trim(($row->unit ?? '') . ' ' . ($row->suburb ?? ''));

                    // Skip addresses with no meaningful address information
                    if (empty($line1) && empty($line2) && empty($row->city) && empty($row->postcode)) {
                        continue;
                    }

                    // Ensure line1 is not empty (required field)
                    if (empty($line1)) {
                        // Use line2 as line1 if line1 is empty, or use city, or provide a default
                        $line1 = $line2 ?: ($row->city ?? 'Unknown Address');
                        $line2 = null; // Clear line2 since we moved it to line1
                    }

                    Address::create([
                        'addressable_type' => $addressableType,
                        'addressable_id' => $addressableId,
                        'type' => $row->type ?? null,
                        'line1' => $line1,
                        'line2' => $line2 ?: null,
                        'city' => $row->city ?? null,
                        'state' => $row->state ?? null,
                        'postcode' => $row->postcode ?? null,
                        'country' => 'AU', // Default to Australia since legacy data doesn't have country
                    ]);
                }
            });
    }

    private function importStatuses(string $connection, int $chunk): void
    {
        $this->info('Importing statuses and statusables...');

        // Import statuses first
        DB::connection($connection)
            ->table('statuses')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    Status::firstOrCreate(
                        ['code' => $row->slug],
                        [
                            'name' => (string) $row->name,
                            'type' => $row->type,
                        ],
                    );
                }
            });

        // Build mapping from legacy status IDs to new status IDs
        $this->info('Building status ID mapping...');
        $statusIdMap = [];
        DB::connection($connection)
            ->table('statuses')
            ->select('id', 'slug')
            ->get()
            ->each(function ($legacyStatus) use (&$statusIdMap): void {
                $newStatus = Status::where('code', $legacyStatus->slug)->first();
                if ($newStatus !== null) {
                    $statusIdMap[$legacyStatus->id] = $newStatus->id;
                }
            });

        $this->info('Status ID mapping created: ' . count($statusIdMap) . ' mappings');

        DB::connection($connection)
            ->table('statusables')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($statusIdMap): void {
                foreach ($rows as $row) {
                    $statusableType = (string) $row->statusable_type;
                    $statusableId = (int) $row->statusable_id;

                    if ($statusableType === 'App\\Models\\Lead') {
                        $contactId = $this->contactId($statusableId);
                        if ($contactId === null) {
                            continue;
                        }
                        $statusableType = Contact::class;
                        $statusableId = $contactId;
                    }

                    // Map legacy status ID to new status ID
                    if (!isset($statusIdMap[$row->status_id])) {
                        continue; // Skip if no mapping found
                    }

                    $newStatusId = $statusIdMap[$row->status_id];

                    DB::table('statusables')->insertOrIgnore([
                        'status_id' => $newStatusId,
                        'statusable_type' => $statusableType,
                        'statusable_id' => $statusableId,
                    ]);
                }
            });
    }

    private function importOnlineformContacts(string $connection, int $chunk): void
    {
        $this->info('Importing onlineform_contacts...');

        DB::connection($connection)
            ->table('onlineform_contacts')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    $modelType = (string) $row->model_type;
                    $modelId = (int) $row->model_id;

                    if ($modelType === 'App\\Models\\Lead') {
                        $contactId = $this->contactId($modelId);
                        if ($contactId === null) {
                            continue;
                        }
                        $modelType = Contact::class;
                        $modelId = $contactId;
                    }

                    OnlineformContact::firstOrCreate([
                        'uuid' => (string) $row->uuid,
                    ], [
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                    ]);
                }
            });
    }

    private function importCampaignWebsites(string $connection, int $chunk): void
    {
        $this->info('Importing campaign_websites and related tables...');

        // Build campaign_website ID mapping (legacy_id → new_id)
        $campaignWebsiteIdMap = [];

        DB::connection($connection)
            ->table('campaign_websites')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$campaignWebsiteIdMap): void {
                foreach ($rows as $row) {
                    $newRecord = CampaignWebsite::create([
                        'organization_id' => $this->organizationId,
                        'name' => (string) $row->title,
                        'domain' => $row->url,
                        'is_active' => (bool) ($row->is_enabled ?? true),
                    ]);
                    $campaignWebsiteIdMap[$row->id] = $newRecord->id;
                }
            });

        $this->info('Campaign website ID mapping created: ' . count($campaignWebsiteIdMap) . ' mappings');

        DB::connection($connection)
            ->table('campaign_website_templates')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    CampaignWebsiteTemplate::firstOrCreate([
                        'slug' => (string) $row->template_id,
                    ], [
                        'name' => (string) $row->name,
                        'schema' => null,
                    ]);
                }
            });

        DB::connection($connection)
            ->table('campaign_website_project')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($campaignWebsiteIdMap): void {
                foreach ($rows as $row) {
                    // Map legacy campaign_website_id to new ID
                    $newCampaignWebsiteId = $campaignWebsiteIdMap[$row->campaign_website_id] ?? null;
                    if ($newCampaignWebsiteId === null) {
                        continue;
                    }

                    // Skip if project doesn't exist
                    if (!DB::table('projects')->where('id', $row->project_id)->exists()) {
                        continue;
                    }

                    DB::table('campaign_website_project')->insertOrIgnore([
                        'campaign_website_id' => $newCampaignWebsiteId,
                        'project_id' => $row->project_id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            });
    }

    private function importResources(string $connection, int $chunk): void
    {
        $this->info('Importing resources, resource_groups, resource_categories...');

        DB::connection($connection)
            ->table('resources')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    // Validate that referenced users exist, otherwise set to null
                    $createdBy = null;
                    if ($row->created_by && DB::table('users')->where('id', $row->created_by)->exists()) {
                        $createdBy = $row->created_by;
                    }

                    $modifiedBy = null;
                    if ($row->modified_by && DB::table('users')->where('id', $row->modified_by)->exists()) {
                        $modifiedBy = $row->modified_by;
                    }

                    Resource::firstOrCreate([
                        'slug' => (string) $row->slug,
                    ], [
                        'name' => (string) $row->title,  // Legacy uses 'title', current uses 'name'
                        'description' => null,  // Legacy table doesn't have description column
                        'url' => $row->url,
                        'created_by' => $createdBy,
                        'modified_by' => $modifiedBy,
                    ]);
                }
            });

        DB::connection($connection)
            ->table('resource_groups')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    ResourceGroup::firstOrCreate([
                        'slug' => (string) $row->slug,
                    ], [
                        'name' => (string) $row->title,  // Legacy column is 'title', not 'name'
                    ]);
                }
            });

        DB::connection($connection)
            ->table('resource_categories')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    ResourceCategory::firstOrCreate([
                        'slug' => (string) $row->slug,
                    ], [
                        'name' => (string) $row->title,  // Legacy column is 'title', not 'name'
                    ]);
                }
            });
    }

    private function importAdAndLayout(string $connection, int $chunk): void
    {
        $this->info('Importing ad_managements, column_management, widget_settings...');

        DB::connection($connection)
            ->table('ad_managements')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    AdManagement::create([
                        'organization_id' => $this->organizationId,
                        'name' => (string) $row->title,  // Legacy uses 'title', current uses 'name'
                        'config' => $this->decodeJson($row->config ?? null),
                    ]);
                }
            });

        DB::connection($connection)
            ->table('column_management')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    // Skip if user doesn't exist
                    $userExists = DB::table('users')->where('id', $row->user_id)->exists();
                    if (!$userExists) {
                        continue;
                    }

                    ColumnManagement::create([
                        'user_id' => $row->user_id,
                        'table' => (string) $row->table,
                        'visible_columns' => $this->decodeJson($row->visible_columns ?? null),
                        'hidden_columns' => $this->decodeJson($row->hidden_columns ?? null),
                    ]);
                }
            });

        DB::connection($connection)
            ->table('widget_settings')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows): void {
                foreach ($rows as $row) {
                    // Skip if user doesn't exist
                    $userExists = DB::table('users')->where('id', $row->user_id)->exists();
                    if (!$userExists) {
                        continue;
                    }

                    WidgetSetting::create([
                        'user_id' => $row->user_id,
                        'widget' => (string) $row->widget,
                        'settings' => $this->decodeJson($row->settings ?? null),
                    ]);
                }
            });
    }

    /**
     * @return array<int, int>|null
     */
    private function mapLeadIdJsonToContactIds(mixed $value): ?array
    {
        $decoded = $this->decodeJson($value);
        if (! is_array($decoded)) {
            return null;
        }

        $mapped = [];
        foreach ($decoded as $legacyLeadId) {
            if (! is_int($legacyLeadId) && ! ctype_digit((string) $legacyLeadId)) {
                continue;
            }
            $contactId = $this->contactId((int) $legacyLeadId);
            if ($contactId !== null) {
                $mapped[] = $contactId;
            }
        }

        return $mapped === [] ? null : $mapped;
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

