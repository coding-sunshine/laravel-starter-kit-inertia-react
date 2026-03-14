# Step 5: Tasks, Relationships, Partners, Marketing, Notes, Comments, Tags, and Content

## Goal

Add **tasks**, **relationships**, **partners**, **mail_lists**, **brochure_mail_jobs**, **website_contacts**, **questionnaires**, **finance_assessments**, **notes**, **comments**, **tags/taggables**, **addresses**, **onlineform_contacts**, **campaign_websites** (and templates, pivot), **resources** (groups, categories), **mail_job_statuses**, **ad_managements**, **statuses/statusables**, **survey_questions**, **column_management**, **widget_settings**, and optional **websites**, **website_pages**, **website_elements**, **wordpress_websites**, **wordpress_templates**. All person FKs use contact_id. Import from MySQL.

## Suggested implementation order (use kit generators first)

1. **Generate models** with `make:model:full` for each entity below (batch or one-by-one); then add plan-specific columns (organization_id, *_contact_id FKs, userstamps, morph columns) to migrations and models.
2. **Run** `make:filament-resource` and/or `make:data-table` for key entities (Task, Partner, MailList, etc.) as needed.
3. **Implement** `fusion:import-tasks-relationships-marketing` and `fusion:verify-import-tasks-marketing`.

**Models to generate (priority):** Task, Relationship, Partner, MailList, BrochureMailJob, MailJobStatus, WebsiteContact, Questionnaire, FinanceAssessment, Note, Comment, Tag, Address, Status, OnlineformContact, CampaignWebsite, CampaignWebsiteTemplate, Resource, ResourceGroup, ResourceCategory, AdManagement, SurveyQuestion, ColumnManagement, WidgetSetting; optionally Website, WebsitePage, WebsiteElement, WordpressWebsite, WordpressTemplate. Example (subset):
```bash
for m in Task Relationship Partner MailList BrochureMailJob WebsiteContact Questionnaire FinanceAssessment Note Comment Tag Address Status; do php artisan make:model:full $m --migration --factory --seed --category=development; done
# Add remaining: MailJobStatus, OnlineformContact, CampaignWebsite, Resource, ResourceGroup, ResourceCategory, AdManagement, SurveyQuestion, ColumnManagement, WidgetSetting, etc.
```
Then add taggables, statusables, campaign_website_project pivot, and morph columns per Deliverables.

**Optional split:** Step 5 can be implemented in two passes if desired: **5a** — tasks, relationships, partners, notes, comments, tags, addresses, statuses; **5b** — mail_lists, brochure_mail_jobs, campaign_websites, resources, etc. Verification remains for the full Step 5.

## Starter Kit References

- **Database**: userstamps, activity log, seeders
- **DataTable**: `docs/developer/backend/data-table.md`
- **Filament**: `docs/developer/backend/filament.md`
- **Actions/Controllers**: CRUD and single-action patterns

## Deliverables

1. **Migrations**
   - **tasks**: id, organization_id, assigned_contact_id (nullable, FK → contacts), attached_contact_id (nullable, FK → contacts), or assigned_to_user_id (nullable) + attached_contact_id; title, description, due_at, priority, status, completed_at, timestamps, soft_deletes, created_by, updated_by.
   - **relationships**: id, organization_id, account_contact_id (FK → contacts), relation_contact_id (FK → contacts), type (string), timestamps.
   - **partners**: id, organization_id, contact_id (FK → contacts), status (enum: active|inactive, default active), partner_type (string, nullable — e.g. referral, affiliate, co-agent), commission_rate (decimal 5,2, nullable — default commission % this partner earns), territory (string, nullable — suburb or region), notes (text, nullable), timestamps, soft_deletes, created_by, updated_by. Index: organization_id, contact_id, status.
   - **mail_lists**: id, organization_id, name, description (text, nullable), created_by (FK → users), timestamps.
   - **mail_list_contacts** (pivot): mail_list_id (FK → mail_lists), contact_id (FK → contacts, nullable), external_email (string, nullable — for non-contact email addresses). Unique(mail_list_id, contact_id) where contact_id not null. One row per email entry.
   - **brochure_mail_jobs**: id, organization_id, owner_contact_id, … (e.g. mail_list_id, status, scheduled_at), client_contact_ids (json or pivot), timestamps, created_by, updated_by.
   - **website_contacts**: id, organization_id, contact_id (FK → contacts), source, … timestamps.
   - **questionnaires**: id, organization_id, contact_id (FK → contacts), … (questionnaire fields), timestamps.
   - **finance_assessments**: id, organization_id, contact_id (FK → contacts, nullable — linked if email matches an existing contact), name (string), email (string), phone (string, nullable), employment_type (enum: employed|self_employed|retired|other), annual_income (decimal 12,2, nullable), other_income (decimal 12,2, nullable), existing_loans (decimal 12,2, nullable), credit_card_limit (decimal 12,2, nullable), deposit_available (decimal 12,2, nullable), property_purpose (enum: owner_occ|investment), preferred_loan_type (enum: variable|fixed|split), notes (text, nullable), submitted_at (timestamp), created_by (FK → users, nullable — null for public submissions), timestamps. Index: organization_id, contact_id, submitted_at.
   - **notes**: id, organization_id (optional), noteable_type, noteable_id (morph: Contact, Project, Sale), author_id (user), content, type, timestamps.
   - **comments**: id, commentable_type, commentable_id (morph), user_id, comment, is_approved, timestamps.
   - **Tags:** Do **not** create custom `tags`/`taggables` tables. Use **spatie/laravel-tags** (kit has it): add **HasTags** to Contact, Project, Sale, Task (and other taggable models); import legacy tag data into Spatie’s schema. See 00-kit-package-alignment.md.
   - **addresses**: id, addressable_type, addressable_id (morph), type, line1, line2, city, state, postcode, country, timestamps.
   - **statuses**: id, name, … **statusables**: status_id, statusable_type, statusable_id (morph).
   - **onlineform_contacts**: id, model_type, model_id (morph → Contact), uuid, timestamps.
   - **campaign_websites**, **campaign_website_templates**, **campaign_website_project** (pivot).
   - **resources**, **resource_groups**, **resource_categories** (created_by, modified_by → user_id). Use **kalnoy/nestedset** for **resource_categories** tree (parent_id, _lft, _rgt). Optional: **genealabs/laravel-governor** for resource ownership (00-kit-package-alignment.md P3).
   - **Email (laravel-database-mail)**: Create **TaskAssignedEvent** (task due reminder); register in `config/database-mail.php`. See 00-kit-package-alignment.md.
   - **Bot In A Box tables** (confirmed on live `/bot-in-a-box` with 10 categories, 50+ bots):
     - **ai_bot_categories**: id, name, slug, icon (emoji/SVG string), display_order (int), organization_id (nullable), timestamps. Use spatie/eloquent-sortable for display_order.
     - **ai_bots**: id, category_id (FK → ai_bot_categories), name, slug, description (text), icon (string, nullable), is_system (boolean, default false — system bots can't be deleted), is_active (boolean, default true), created_by (FK → users, nullable), timestamps.
     - **ai_bot_prompts**: id, bot_id (FK → ai_bots), label (string), prompt_template (text), sort_order (int), timestamps.
     - **ai_bot_runs**: id, bot_id (FK → ai_bots), user_id (FK → users), input_context (text, nullable), prompt_used (text), output (text), model_used (string), realtime_data_injected (boolean, default false), created_at.
   - **mail_job_statuses** (for brochure_mail_jobs).
   - **advertisements** (was `ad_managements`): id, title, image_path (string — stored via Spatie Media Library), link_url (string, nullable), status (enum: active|inactive, default active), display_order (int, default 0), created_by (FK → users), timestamps. Index: status, display_order.
   - **column_management**, **widget_settings** (user_id).
   - **survey_questions** (standalone or linked to questionnaires).
   - **websites**, **website_pages**, **website_elements**, **wordpress_websites**, **wordpress_templates** (user_id) if needed.

2. **Models**
   - Task, Relationship, Partner, MailList, BrochureMailJob, WebsiteContact, Questionnaire, FinanceAssessment, Note, Comment, Tag, Address, Status, OnlineformContact, CampaignWebsite, Resource, AdManagement, etc. — with Contact relationships and userstamps/LogsActivity where appropriate.

3. **Import command**
   - `php artisan fusion:import-tasks-relationships-marketing`. Source: MySQL tasks, relationships, partners, mail_lists, brochure_mail_jobs, mail_job_statuses, website_contacts, questionnaires, finance_assessments, **notes**, **comments**, **tags** (import into spatie/laravel-tags), **addresses**, **onlineform_contacts**, **campaign_websites**, **campaign_website_templates**, **campaign_website_project**, **resources**, **resource_groups**, **resource_categories**, **ad_managements**, **statuses**, **statusables**, **survey_questions**, **column_management**, **widget_settings**, **websites**, **website_pages**, **website_elements**, **wordpress_websites**, **wordpress_templates**, **default_fonts**, **file_types**. Target: new PostgreSQL tables.
   - **Mapping**:
     - tasks: assigned_id → assigned_contact_id; attached_id → attached_contact_id (via lead_id→contact_id map). If assigned to user, optionally set assigned_to_user_id or resolve user’s contact_id.
     - relationships: account_id → account_contact_id, relation_id → relation_contact_id.
     - partners: lead_id → contact_id.
     - mail_lists: lead_id → owner_contact_id; client_ids (json) → contact_ids via map.
     - brochure_mail_jobs: lead_id → owner_contact_id; client_ids → client_contact_ids.
     - website_contacts: lead_id → contact_id; subscriber_id → optional subscriber_contact_id.
     - questionnaires: lead_id → contact_id.
     - finance_assessments: loggedin_agent_id → agent_contact_id or logged_in_user_id.
     - **notes**: noteable_type = Lead → Contact, noteable_id → contact_id via map; author_id → user_id (map if user ids changed).
     - **comments**: commentable_type/commentable_id (e.g. Sale); commentable_id → new sale id when type is Sale; user_id → user.
     - **tags:** Import into **spatie/laravel-tags** schema; taggable_type = Lead → Contact (and Project, Sale, Task as needed), taggable_id → contact_id (or new id).
     - **addresses**: addressable_type = Lead → Contact, addressable_id → contact_id.
     - **onlineform_contacts**: model_type = Lead → Contact, model_id → contact_id.
     - **statuses/statusables**: statusable_type = Lead → Contact, statusable_id → contact_id.
     - campaign_websites: user_id → new user_id; campaign_website_project: project_id map if needed.
     - resources: created_by, modified_by → user_id.
     - ad_managements: created_by, updated_by → user_id.
     - column_management, widget_settings: user_id.

4. **Filament / DataTables / Inertia**
   - Filament resources for Task, Partner, MailList, etc. **TaskDataTable** (and other CRM DataTables in this step): add **HasAi** (see 00-kit-package-alignment.md). Run **php artisan models:audit** at end of step to verify model conventions. Optional Inertia pages.

## DB Design (this step)

- New tables as above; all person FKs use contact_id or named *_contact_id. See `00-database-design.md` § 3.5, 3.6.

## Data Import

- **Source**: MySQL tables listed above.
- **Target**: New PostgreSQL tables. Every lead_id and client_id/agent_id style column replaced by contact_id mapping.

## UI Specification

### Task List Layout

Primary view: **grouped by due status** (Due Today / Overdue / Upcoming 7 Days / No Date). Each group has an inline quick-create row at the top (single text field + date picker + assign-to dropdown). Clicking “Due Today” header collapses/expands group.

Secondary view: flat **DataTable** for search/filter all tasks.

Inline status toggle: checkbox at row start marks task complete; row animates out and moves to “Completed” group. Priority badge (low=gray, medium=blue, high=orange, urgent=red) inline.

**Filter bar**: assigned_to | contact | type | date range | priority.

### Create/Edit Task Form (slide-over or quick-add popover)

```
Title* | Due Date | Priority (low/medium/high/urgent)
Type (call / email / meeting / follow-up / other)
Linked Contact (searchable, Typesense) | Linked Project (optional)
Assigned To (default: current user)
Notes (optional textarea)
Recurring? (toggle → frequency picker: daily/weekly/monthly)
```

Quick-create mode (popover): only Title + Due Date + Assign shown; expands to full form on “More options”.

### DataTable Configuration — TaskDataTable

**Traits**: `HasExport, HasToggle, HasSelectAll, HasInlineEdit, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `title` | `text` | searchable, inlineEdit |
| `contact.full_name` | `text` | searchable, filterable |
| `type` | `badge` | call/email/meeting/follow-up, colorMap |
| `priority` | `badge` | low/medium/high/urgent, colorMap |
| `due_at` | `date` | sortable, colorCode: overdue=red, today=amber |
| `assigned_to` | `avatar_text` | filterable |
| `is_completed` | `toggle` | HasToggle |
| `created_at` | `date` | hidden by default |

**Options**:
```php
'globalSearch' => true,
'columnFilters' => true,
'groupBy' => ['due_status', 'assigned_to'],
'savedViews' => true,
'bulkActions' => ['complete', 'reassign', 'export'],
'inlineEditColumns' => ['title', 'due_at', 'priority'],
'rowClickAction' => 'slideOver',
```

**`tableQuickViews()`**:
```php
return [
    QuickView::make('today')->label('Due Today')->default()->filter(['due_at' => 'today', 'is_completed' => false]),
    QuickView::make('overdue')->label('Overdue')->filter(['due_at' => ['before' => 'today'], 'is_completed' => false]),
    QuickView::make('upcoming')->label('Upcoming 7 Days')->filter(['due_at' => ['next_days' => 7]]),
    QuickView::make('my_tasks')->label('My Tasks')->filter(['assigned_to_id' => 'auth_user']),
    QuickView::make('completed')->label('Completed')->filter(['is_completed' => true]),
];
```

**`tableAnalytics()`**:
```php
return [
    Analytic::make('due_today')->label('Due Today')->query(fn($q) => $q->whereDate('due_at', today())->where('is_completed', false)->count()),
    Analytic::make('overdue')->label('Overdue')->query(fn($q) => $q->whereDate('due_at', '<', today())->where('is_completed', false)->count()),
    Analytic::make('completed_week')->label('Completed This Week')->query(fn($q) => $q->where('is_completed', true)->whereBetween('completed_at', [now()->startOfWeek(), now()])->count()),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a CRM task list for real estate agents. Tasks are follow-up actions linked to
contacts. Types: call, email, meeting, follow-up. Priority: low/medium/high/urgent.
Help identify overdue tasks, agents with heavy workloads, and contacts without recent follow-up activity.';
```

### Mail List — Full Spec (Confirmed from Live v3 — `/mail-list`)

Create named mailing lists by combining existing contacts + external email addresses. Used for campaign brochure sends.

**Page** (`/mail-list`):
- DataTable: list name, contact count, external email count, created_by, created_at, actions
- Columns: `name` (searchable), `contact_count` (computed), `external_count` (computed), `created_by.name` (avatar_text), `created_at` (date)
- Row actions: Edit, Delete, Export emails (CSV), Send Brochure (→ BrochureMailJob)

**Create/Edit Mail List** (slide-over):
```
Name* | Description (optional textarea)
Contacts (multi-select Typesense search — add existing contacts)
External Emails (textarea — one email per line or comma-separated; stored as mail_list_contacts rows with contact_id=null)
```

**Export**: “Export List” → CSV of all emails (contact emails + external emails) in the list.

---

### Finance Assessment Form — Full Spec (Confirmed from Live v3 — 4th online form type)

> **Source**: Live has 4 public form types: Property Enquiry, Property Search, Property Reservation, **Finance Assessment**. The Finance Assessment was missing from the plan.

**Public form** (`/forms/finance-assessment`) — No authentication required:
```
Section 1 — Personal Details
  Full Name* | Email* | Phone

Section 2 — Employment
  Employment Type* (Employed / Self-Employed / Retired / Other)
  Annual Income | Other Income

Section 3 — Liabilities
  Existing Loan Repayments (monthly) | Credit Card Limit

Section 4 — Purchase Details
  Deposit Available | Property Purpose (Owner-Occupied / Investment)
  Preferred Loan Type (Variable / Fixed / Split)

Section 5 — Notes (optional)
  Additional notes or questions
```

On submit:
1. Creates `FinanceAssessment` record
2. If email matches existing Contact → sets `contact_id` (or creates new Contact with stage = `property_enquiry`)
3. Sends confirmation email to submitter (laravel-database-mail `FinanceAssessmentReceivedEvent`)
4. Notifies admin via Reverb broadcast (if Reverb configured)

**Admin page** (`/admin/finance-assessments`): DataTable — name, email, employment_type, annual_income, property_purpose, submitted_at, linked contact (if any), actions (Link to contact, archive).

---

### Advertisement Management — Full Spec (Confirmed from Live v3 — `/ad-management`)

> **Source**: Live `/ad-management` has 11 active ads in a carousel on the dashboard.

**Admin page** (`/admin/ad-management`):
- DataTable with `HasReorder` (drag `display_order`), `HasToggle` (status active/inactive), image upload
- Columns: title, image (thumbnail), link_url, status toggle, display_order (drag handle), created_at, actions
- Create/Edit slide-over: title, image upload (Spatie Media Library), link URL (optional), status toggle
- Bulk actions: Activate, Deactivate, Delete

**Dashboard carousel** (`<AdCarousel>` React component):
- Fetches `active` ads sorted by `display_order ASC`; cached for 60s (spatie/laravel-responsecache or simple cache)
- Auto-rotates every 5 seconds (CSS transition or Embla Carousel — check starter kit for carousel component)
- Click → opens `link_url` in new tab (if set)
- Responsive: full-width on mobile, partial on desktop

**All Ads public gallery** (`/all-ads` or `/other-services`) — Subscriber-visible:
- Grid of all `status = active` ads sorted by `display_order`
- Card: image + title + link button (if link_url set)
- No edit capability; purely display

---

### Bot In A Box — Full Spec (Confirmed from Live v3 — `/bot-in-a-box`)

> **Source**: Live `/bot-in-a-box` has 10 categories, 50+ bots, each with prompt templates, context input, real-time data toggle, and streaming output.

**Page** (`/bot-in-a-box`) — Bot selection:
```
Left sidebar: Category list (icon + name, collapsible, from ai_bot_categories)
Right: Bot grid — cards per active bot in selected category
  Bot card: icon, name, description, [Use Bot] button
```

**Bot run page** (`/bot-in-a-box/{bot:slug}/run`):
```
Header: Bot name + category breadcrumb + [← Back] link

Prompt chips: one chip per ai_bot_prompts row (click → fills textarea below)
Custom context: textarea* “Add context, e.g. contact name, project details”

Options row (collapsible Advanced Options):
  Real-time Data toggle → if enabled, injects CrmContextTool data into system prompt
  Model selector (select from available OpenRouter models — default deepseek-r1 free)
  Temperature slider (0.0–1.0, default 0.7)

[Generate] button → streams response via SSE to output panel

Output panel:
  Thesys C1 renders response (interactive cards/text — NOT bare <p>)
  [Copy to clipboard] button
  [Save to Notes] button (saves to Contact notes if context includes a contact)

History section (collapsible): last 5 ai_bot_runs for this bot for current user
```

**Real-time Data toggle** (injects `CrmContextTool` output):
```php
// CrmContextTool — Laravel AI SDK tool
// Returns: today's tasks (5), recent contacts (3), active reservations (3)
// Injected as JSON into agent system prompt when toggle enabled
class CrmContextTool extends AbstractTool
{
    public function handle(): string
    {
        return json_encode([
            'today_tasks' => Task::where('assigned_to_user_id', auth()->id())
                                  ->whereDate('due_at', today())->limit(5)->get(['title', 'due_at']),
            'recent_contacts' => Contact::latest()->limit(3)->get(['full_name', 'stage', 'last_contacted_at']),
            'active_reservations' => PropertyReservation::active()->limit(3)->get(['id', 'stage', 'settlement_date']),
        ]);
    }
}
```

**SSE streaming**: Use `StreamedResponse` (Laravel native) or Reverb for SSE. Bot run endpoint: `POST /bot-in-a-box/{bot}/run` → starts stream. Frontend: `EventSource` or TanStack query streaming.

**Admin** (`/admin/bots`):
- CRUD for categories, bots, prompts via Filament resources
- System bots (`is_system = true`) are read-only; custom bots editable by admin
- Subscribers can create custom bots if `AiBotsCustomFeature` Pennant flag is enabled for their org

**Feature flag**: Gate custom bot creation behind `AiBotsCustomFeature` (create in Step 0 alongside other CRM feature flags).

---

## AI Enhancements

- **Smart task suggestions**: After a new contact is created or stage changes, AI suggests an initial follow-up task (“Call within 2 days for new enquiry”). Shown as a dismissible banner on the contact record. Triggered by `ContactCreated` event → Prism call.
- **Overdue escalation**: Daily scheduled job → if task overdue >3 days, HasAi `insights` highlights the pattern in the TaskDataTable insights panel. Surfaced as C1 insight card.
- **Task suggestions from stage/history**: Use Prism to suggest next tasks for a contact (e.g. “Follow up”, “Send proposal”) based on contact stage and recent activity. Expose on contact show page or task index.
- **Mail list segmentation**: Optional AI to suggest tags or segments for contacts (e.g. “High value”, “Needs follow-up”) for use in mail lists.
- **Note summarization**: Summarize last 5 notes for a contact (e.g. “John has expressed interest in SMSF properties, budget $650k, prefers Brisbane North”). Prism → C1 generic insight card on contact timeline.
- **Voice-to-task** (Phase 2): OpenAI Whisper transcription → Prism extracts task title/due/contact → creates task. Entry point: mic button in quick-create form.
- *More ideas (email draft from contact, relationship insight):* **13-ai-native-features-by-section.md** § Step 5.

## Verification (verifiable results, data fully imported)

- After import, verify row counts against MySQL replica: **tasks** = 53, **relationships** = 7,304, **partners** = 298, **mail_lists** = 66, **brochure_mail_jobs** = 180, **mail_job_statuses** = 180, **website_contacts** = 1,218, **notes** = 22,418, **comments** = 4,197, **addresses** = 24,508, **statusables** = 147,913, **statuses** = 214, **campaign_websites** = 199, **campaign_website_project** = 327, **resources** = 138, **column_management** = 4,430; all contact/organization FKs resolve. Implement `php artisan fusion:verify-import-tasks-marketing`. See **11-verification-per-step.md** § Step 5 and **10-mysql-usage-and-skip-guide.md**.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 6 until the human has completed the checklist below.**

Human must:
- [ ] Confirm all migrations for this step ran (tasks, relationships, partners, notes, comments, tags, addresses, statuses/statusables, campaign_websites, resources, etc.).
- [ ] Confirm `fusion:import-tasks-relationships-marketing` completed; verification PASS (expected counts for tasks, relationships, notes, comments, addresses, statusables, campaign_websites, resources, column_management, etc.).
- [ ] Confirm no broken contact_id or user_id FKs.
- [ ] Optionally spot-check tasks, a contact’s notes, and a mail list in the UI.
- [ ] Approve proceeding to Step 6 (AI-native features).

## Acceptance Criteria

- [ ] All migrations run.
- [ ] Import command runs and populates all listed tables with correct contact_id (and user_id) references.
- [ ] Verification confirms expected row counts for tasks, relationships, partners, notes, comments, addresses, statusables, campaign_*, resources, column_management, etc.

**UI acceptance criteria (verified by Visual QA protocol — Task 6 checks):**
- [ ] `/tasks` renders in grouped layout (Due Today / Overdue / Upcoming / No Date)
- [ ] Each group has inline quick-create row (title input + date + assign fields)
- [ ] Task rows show priority badge (colored), due date (red if overdue), contact link
- [ ] Inline checkbox marks task complete (row animates out or moves to Completed group)
- [ ] TaskDataTable quick views visible (tabs or chip row): Due Today / Overdue / Upcoming / My Tasks / Completed
- [ ] Task create slide-over opens with all fields including Recurring toggle
- [ ] Contact detail page shows task suggestions banner after new contact created (HasAi smart suggestion)
