# Step 1: Contacts (Person Entity) and CRM Roles

## Goal

Introduce the **Contact** (person) entity and contact detail tables. Seed CRM-specific roles/permissions. No User↔Contact link yet (Step 2). This step establishes the new data model that replaces “lead everywhere.”

Two lead contexts: **property** (default; created by superadmin/admin/subscribers) and **saas_product** (v4 product leads — Step 23).

## Suggested implementation order (use kit generators first)

1. **Generate models** with `make:model:full` for each entity below; then add plan-specific columns (organization_id, legacy_lead_id, userstamps, soft deletes) to migrations and models.
2. **Create contact_emails and contact_phones** migrations and models (simple; no full seeder if data comes only from import).
3. **Run** `php artisan make:filament-resource Contact --generate --view` (optional) and `php artisan make:data-table Contact --export --route` (optional).
4. **Seed** CRM roles/permissions (RolesAndPermissionsSeeder or **sync:route-permissions**; also **permission:sync**, **permission:health** — see 00-kit-package-alignment.md; kit uses sync:route-permissions, not permission:sync-routes).
5. **Implement** `fusion:import-contacts` and `fusion:verify-import-contacts`.

**Models to generate:** Contact, Source, Company. Example (run from new app root):
```bash
php artisan make:model:full Contact --migration --factory --seed --category=essential
php artisan make:model:full Source --migration --factory --seed --category=essential
php artisan make:model:full Company --migration --factory --seed --category=essential
```
Then add ContactEmail and ContactPhone (migration + model only, or minimal make:model). After generation, edit migrations to add organization_id, legacy_lead_id (on Contact), userstamps, and contact_emails/contact_phones schema per Deliverables below.

## Starter Kit References

- **Database**: `docs/developer/backend/database/README.md`, `docs/developer/backend/database/seeders.md`
- **Migrations**: `database/migrations/` — follow existing patterns (foreignId, constrained, index, timestamps)
- **Userstamps**: `docs/developer/backend/userstamps.md` — `created_by`, `updated_by` → users
- **Activity log**: `docs/developer/backend/activity-log.md` — `LogsActivity`, `getActivitylogOptions()`
- **Permissions**: `docs/developer/backend/permissions.md` — Spatie, route-based, **sync:route-permissions** (kit command), **permission:sync**, **permission:health**; seeders
- **Models**: Use `php artisan make:model:full Contact --category=essential --all` (or equivalent) so migration, factory, seeder, activity logging are generated
- **Organizations**: Add `organization_id` to new tables where content is org-scoped; use `BelongsToOrganization` / `OrganizationScope` if present in kit

## Deliverables

1. **Migrations**
   - **contacts**: id, organization_id (nullable, FK → organizations), **contact_origin** (string or enum: `property`, `saas_product`, default `property`), first_name, last_name, job_title, type (string or enum: lead, client, agent, partner, subscriber, bdm, affiliate), stage (string, nullable), source_id (nullable, FK → sources), company_id (nullable, FK → companies) or company_name (nullable), extra_attributes (json), last_followup_at, next_followup_at, **last_contacted_at** (nullable timestamp — updated on every email/call/note; used for Days-Since-Contact badge and stale Smart Lists), **lead_score** (nullable smallint 0–100 — AI-computed or rule-based; updated by Step 6 agent; see 00-ui-design-system.md §7 and 13-ai-native-features-by-section.md), legacy_lead_id (nullable, for import map), timestamps, soft_deletes, created_by, updated_by (FK → users). Indexes: organization_id, contact_origin, type, stage, legacy_lead_id, last_contacted_at.
- **sources** (lookup): id, organization_id (nullable), name, … timestamps. Import from MySQL sources.
- **companies** (lookup): id, organization_id (nullable), name, … timestamps. Import from MySQL companies.
   - **contact_emails**: id, contact_id (FK → contacts, cascade), type (string, nullable), value (string), is_primary (boolean, default false), order_column (nullable), timestamps.
   - **contact_phones**: id, contact_id (FK → contacts, cascade), type (string, nullable), value (string), is_primary (boolean, default false), order_column (nullable), timestamps.

2. **Models**
   - **Contact**: Fillable, casts (extra_attributes via **Spatie\SchemalessAttributes\Casts\SchemalessAttributes** — see 00-kit-package-alignment.md; dates), SoftDeletes, Userstamps, LogsActivity, BelongsToOrganization (if kit has it). **Stage**: use **spatie/laravel-model-states** with the **14 confirmed contact stages** from the live v3 site (see § Contact Stages below); use **a909m/filament-statefusion** for transition UI in Filament. **Scout**: add **Searchable** + `toSearchableArray()`; configure Typesense collection for contacts. **Soft cascade**: use **askedio/laravel-soft-cascade** so contact_emails and contact_phones are soft-deleted when Contact is soft-deleted. Relationships: organization, contactEmails, contactPhones, createdBy, updatedBy.
   - **ContactEmail**, **ContactPhone**: Fillable, belongsTo Contact.

3. **Seeders**
   - **Essential**: Seed default contact types/stages and **contact_origin** values (`property`, `saas_product`) if using enums or config. Seed CRM permissions (e.g. `view contacts`, `create contacts`, `edit contacts`, `delete contacts`, `view projects`, …) and CRM roles (e.g. `sales-agent`, `bdm`, `referral-partner`, subscriber) — see `docs/developer/backend/database/seeders.md` and `RolesAndPermissionsSeeder` pattern. Optionally run **sync:route-permissions** after adding routes in later steps.

4. **Filament (optional)**
   - Register Contact resource in Filament admin (list, form, filters by contact_origin, type, stage, organization; default property for CRM; superadmin sees saas_product). Use **filament-statefusion** for stage transition UI. Use kit’s Filament patterns from `docs/developer/backend/filament.md`.

5. **DataTable (optional)**
   - `App\DataTables\ContactDataTable` with columns: checkbox, name (first+last + avatar), source dot, stage dot+label, **last_contacted_at** (formatted as color-coded "X days ago" badge per `00-ui-design-system.md` §7), **lead_score** badge (0–100, orange), assigned_agent, tags, quick-action buttons. Add **HasAi** (NLQ, column insights, optional Thesys Visualize — requires DATA_TABLE_AI_MODEL; see 00-kit-package-alignment.md) and **HasAuditLog** (compliance logging for inline edits). Follow `docs/developer/backend/data-table.md` and Users table example. Support filter by contact_origin so property and SaaS lead lists can be shown separately. Left sub-panel: **Smart List sidebar** (follow pattern in `00-ui-design-system.md` §7 — Follow Up Boss model).

6. **Inertia page (optional)**
   - Contacts index/table page that uses ContactDataTable; route and controller following kit’s `UsersTableController` pattern.

## DB Design (this step)

- New tables: **contacts**, **contact_emails**, **contact_phones**, **sources**, **companies**, **smart_lists**. All other tables unchanged.

- **`smart_lists`** migration:

  ```php
  Schema::create('smart_lists', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
      $table->string('name', 100);
      $table->jsonb('filter_state')->default('{}');
      $table->integer('sort_order')->default(0);
      $table->timestamps();
      $table->index(['user_id', 'sort_order']);
  });
  ```

## Data Import

- **Source**: MySQL `leads`, polymorphic `contacts` (contactable), **sources**, **companies**.
- **Target**: PostgreSQL **contacts**, **contact_emails**, **contact_phones**; optional **sources**, **companies** (if separate lookup tables).
- **Mapping**:
  - **sources**: Import as lookup; preserve id or build source_id map. contacts.source_id references sources.
  - **companies**: Import **companies** table; add contact.company_id (nullable) or store company_name on contact only.
  - For each row in `leads`: insert into `contacts` with **contact_origin = 'property'**, first_name, last_name, job_title, type (derive from is_partner or default 'lead'), stage (e.g. 'new' if is_new), source_id, company_id or company_name, extra_attributes, last_followup_at, next_followup_at, timestamps, soft_deletes. Store old `leads.id` in a mapping table or column (e.g. `import_mapping.leads` or contact.legacy_lead_id) for use in Step 2 and later imports.
  - For each row in polymorphic `contacts` where model_type = Lead: resolve model_id to new contact_id via lead_id→contact_id map; insert email/phone into **contact_emails** or **contact_phones** by type (value, type, is_primary).
- **Script/command**: `php artisan fusion:import-contacts`. Read from MySQL connection (`mysql_legacy` in config/database.php). Run idempotently (e.g. skip or update if contact exists by legacy_lead_id or external key).

## UI Specification

### Contact List (DataTable, table-first layout)

Primary view is a DataTable with **column visibility toggles**, **group-by** (stage or assigned_agent), and a **bulk-action bar** (assign, tag, export, archive). Default columns: full name (linked), email, stage badge, lead_score badge, last_contacted_at (color-coded "X days ago"), assigned_agent avatar, suburb, tags.

**Smart List sidebar** (240px left panel, collapsible):
- Pre-built filter sets saved as "smart lists" (e.g. Hot Leads, Needs Follow-up, My Contacts)
- Each list shows a count badge; click to apply filter set to table
- Drag-to-reorder lists; collapse/expand with chevron
- "+" button to save current filter as new smart list
- AI-suggested smart lists (dismissible chips below saved lists, from HasAi `suggest` handler)

### Contact Record Layout (3-panel, unified timeline — NO tabs)

> **Design system authority:** `00-ui-design-system.md §7` — "No separate tabs for Notes, Emails, Tasks — all in one feed. Filter chips to narrow." This overrides any reference to a tab bar on the contact record. Chief must NOT implement tabs.

```
┌──────────────────────────────────────────────────┐
│  [Avatar] Full Name     lead_score badge  [···]  │
│  email · phone · suburb · stage badge            │
├──────────┬──────────────────────┬────────────────┤
│ Smart    │  [+ Note] [Log Call] │ RHS Summary    │
│ List     │  [Send Email] pinned │ ─────────────  │
│ Sidebar  │  ──────────────────  │ Assigned agent │
│ (220px)  │  Filter chips:       │ Days-since-    │
│  fixed   │  All·Email·Call·Note │ contact badge  │
│  border-r│  Task·Property       │ Stage dropdown │
│          │  ──────────────────  │ Tags           │
│          │  Unified timeline    │ Quick actions: │
│          │  (newest first)      │ Email/Call/Task│
│          │  all event types     │                │
└──────────┴──────────────────────┴────────────────┘
```

**Unified timeline** (NOT tabs): All activity in one chronological feed. Filter chips above the feed allow narrowing by type. Each entry:

```
[●] Note added · 2h ago       "Called, left voicemail"         [Edit] [Delete]
[✉] Email sent · Yesterday    "Finance pre-approval follow-up"  [View] [Reply]
[☎] Call logged · 3 days ago  Duration: 4m · Outcome: Interested  [Log outcome]
[🏠] Property viewed · 4 days ago  "Aria Residences, Lot 14"    [View lot]
[✓] Task completed · 1 week ago   "Send project brochure"
[→] Stage changed · 2 weeks ago   New → Qualified
```

Event icons: email (envelope), call (phone), task-completed (check), note (pencil), reservation (key), stage-change (arrow). Actor avatar on each entry. "Load more" at bottom (cursor-based pagination).

**Action bar** (pinned above timeline): `[+ Note]` `[Log Call]` `[Send Email]` `[+ Task]` — these open inline forms or slide-overs, not page navigations.

**RHS Summary panel** (right column, ~40% width): days-since-contact badge (green <7d, amber 7–30d, red >30d), stage as inline dropdown (triggers model-state transition via filament-statefusion endpoint), tag chips with "+ add tag", quick-action buttons (Email draft via Prism + C1, Log call, Add task). Property Interests section (preferences: bed/bath/budget from contact extra_attributes). Related contacts (relationship links).

### Create/Edit Contact Form (slide-over, `max-w-2xl`)

```
Section 1 — Identity
  Full Name* | Email* | Phone | Mobile

Section 2 — Classification
  Stage (model-state dropdown) | Source | Contact Type
  Lead Score (read-only badge, AI-computed)

Section 3 — Location
  Suburb (devrabiul/laravel-geo-genius autocomplete) | State | Postcode

Section 4 — Assignment
  Assigned Agent (searchable user select) | Tags (HasTags multi-select)

Section 5 — Preferences (collapsible)
  Bedrooms min/max | Budget min/max | Property Types (checkboxes)
  Contact Origin (property / saas_product) — defaults to 'property'
```

Save → close slide-over, flash toast, DataTable row refreshes via Inertia partial reload.

**Duplicate detection**: On email `blur`, POST `/contacts/check-duplicate`. If match found, show inline warning banner: "A contact with this email exists — [View existing contact ↗]". Allow override with "Save anyway" for intentional duplicates (e.g. joint buyers sharing email).

### DataTable Configuration — ContactDataTable

**Traits**: `HasExport, HasImport, HasInlineEdit, HasToggle, HasSelectAll, HasAuditLog, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `full_name` | `text` | searchable, sortable, linkTo contact record |
| `email` | `email` | searchable |
| `phone` | `text` | — |
| `stage` | `badge` | colorMap per stage value |
| `lead_score` | `badge` | colorFn: `<30`=red, `30–60`=amber, `>60`=green |
| `last_contacted_at` | `date` | fromNow, colorCode: `<7d`=green, `7–30d`=amber, `>30d`=red |
| `assigned_agent` | `avatar_text` | avatar + name, filterable |
| `suburb` | `text` | filterable |
| `tags` | `tags` | multi-filter |
| `created_at` | `date` | sortable, hidden by default |
| `is_archived` | `toggle` | HasToggle target |

**Options**:
```php
'globalSearch' => true,
'columnFilters' => true,
'groupBy' => ['stage', 'assigned_agent'],
'layoutSwitcher' => ['table', 'card'],
'masterDetail' => true, // expand row → latest activity + tasks
'conditionalFormatting' => true,
'savedViews' => true,
'bulkActions' => ['assign', 'tag', 'export', 'archive'],
'stickyHeader' => true,
'rowsPerPage' => [25, 50, 100],
'rowClickAction' => 'navigate', // Inertia push to contact record
```

**`tableQuickViews()`**:
```php
return [
    QuickView::make('all')->label('All Contacts')->default()
        ->filter(['contact_origin' => 'property']), // Subscribers see property contacts only by default
    QuickView::make('hot')->label('Hot Leads')->filter(['lead_score' => ['gte' => 70], 'contact_origin' => 'property']),
    QuickView::make('stale')->label('Needs Follow-up')->filter(['last_contacted_at' => ['days_ago_gte' => 30], 'contact_origin' => 'property']),
    QuickView::make('new_this_week')->label('New This Week')->filter(['created_at' => ['days_ago_lte' => 7], 'contact_origin' => 'property']),
    QuickView::make('my_contacts')->label('My Contacts')->filter(['assigned_agent_id' => 'auth_user', 'contact_origin' => 'property']),
    QuickView::make('buyers')->label('Buyers')->filter(['contact_type' => 'buyer', 'contact_origin' => 'property']),
    QuickView::make('saas_leads')->label('SaaS Leads')->filter(['contact_origin' => 'saas_product'])
        ->visible(fn() => auth()->user()?->hasRole(['superadmin', 'admin'])), // Superadmin/admin only
    QuickView::make('archived')->label('Archived')->filter(['is_archived' => true]),
];
```

> **`contact_origin` scoping rule**: Subscribers (role: subscriber) should NEVER see `contact_origin = saas_product` contacts — these are internal v4 product leads managed by superadmin/admin. Enforce in the DataTable base query: `->when(auth()->user()?->hasRole('subscriber'), fn($q) => $q->where('contact_origin', 'property'))`. All 8 QuickViews except `saas_leads` already filter to `property`. The `saas_leads` view is hidden for subscribers via the `visible()` callback.

**`tableAnalytics()`**:
```php
return [
    Analytic::make('total')->label('Total Contacts')->query(fn($q) => $q->count()),
    Analytic::make('hot_leads')->label('Hot Leads (70+)')->query(fn($q) => $q->where('lead_score', '>=', 70)->count()),
    Analytic::make('stale')->label('Stale (30+ days)')->query(fn($q) => $q->whereDate('last_contacted_at', '<=', now()->subDays(30))->count()),
    Analytic::make('new_week')->label('New This Week')->query(fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()])->count()),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a CRM contacts table for a real estate agency. Contacts represent buyers,
investors, and vendors. Key fields: stage (new/qualified/hot/warm/cold/dead), lead_score (0–100,
AI-generated), last_contacted_at (days since last interaction), assigned_agent.
Help agents identify who to follow up with and surface pipeline insights.';
```

## Contact Stages — All 14 (Confirmed from Live v3 Site)

> **Source**: Live audit of `/users/client` — 14 distinct stages visible as filter options and inline badges. These **replace** the placeholder "New → Qualified → Proposal → Converted → Archived" example.

### Stage State Classes

Create one `App\States\Contact\{StageName}` class per stage using `spatie/laravel-model-states`. Each class extends `ContactStage` (abstract base). Wire transitions in `ContactStage::$allowedTransitions` or use open transitions (any → any) for CRM flexibility.

| # | Stage Value (string) | Label | Suggested Badge Color |
|---|---|---|---|
| 1 | `new` | New | blue |
| 2 | `nurture` | Nurture | purple |
| 3 | `not_interested` | Not Interested | slate |
| 4 | `hot` | Hot | red |
| 5 | `settlement_handover` | Settlement/Handover | teal |
| 6 | `call_back` | Call Back | orange |
| 7 | `unconditional` | Unconditional | green |
| 8 | `property_reserved` | Property Reserved | amber |
| 9 | `signed_contract` | Signed Contract | lime |
| 10 | `crashed` | Crashed | gray |
| 11 | `bc_required` | BC Required | yellow |
| 12 | `land_settled` | Land Settled | cyan |
| 13 | `construction` | Construction | indigo |
| 14 | `property_enquiry` | Property Enquiry | violet |

### DataTable Badge Color Map (ContactDataTable)

```php
// In ContactDataTable stage column definition:
'colorMap' => [
    'new'                  => 'blue',
    'nurture'              => 'purple',
    'not_interested'       => 'slate',
    'hot'                  => 'red',
    'settlement_handover'  => 'teal',
    'call_back'            => 'orange',
    'unconditional'        => 'green',
    'property_reserved'    => 'amber',
    'signed_contract'      => 'lime',
    'crashed'              => 'gray',
    'bc_required'          => 'yellow',
    'land_settled'         => 'cyan',
    'construction'         => 'indigo',
    'property_enquiry'     => 'violet',
],
```

### Seeder

`DatabaseSeeder` (or `ContactStagesSeeder`): Seed the 14 stage values to a `contact_stages` config table (optional) OR purely rely on the state classes — state machine does not require a DB table, just PHP classes. Seed the string values to `config/contact_stages.php` for reference in DataTable color maps, filters, and NLQ context.

### Quick Views — updated with all 14 stages

Add a `tableQuickViews()` group `'by_stage'` with the 5 most-used stages as fixed tabs: Hot / New / Nurture / Property Reserved / Settled. The full 14-stage filter is available via column filter dropdown.

---

## AI Enhancements

- **Lead Score computation**: Background job triggered on contact save + new activity. Inputs: days-since-contact, completed tasks count, email opens (backstage/laravel-mails), reservation history, budget completeness. Score 0–100 stored in `contacts.lead_score`. UI: colored badge (red <30, amber 30–60, green >60). See `13-ai-native-features-by-section.md` § Real Estate–Specific.
- **Lead Score computation**: Background job triggered on contact save + new activity. Inputs: days-since-contact, completed tasks count, email opens (backstage/laravel-mails), reservation history, budget completeness. Score 0–100 stored in `contacts.lead_score`. UI: colored badge (red <30, amber 30–60, green >60). See `13-ai-native-features-by-section.md` § Real Estate–Specific.

- **Days-Since-Contact** — events that update `contacts.last_contacted_at`:
  - Email **sent** (outbound — `MessageSent` or task type=email completed)
  - Call **logged** (task type=call + is_completed = true)
  - Note **added** manually (observer on Note create for noteable_type=Contact)
  - Task **completed** (any task type where is_completed flips to true)
  - **NOT updated**: email opened, page view, task created, contact record viewed
  - Implementation: `ContactActivityListener` bound to `TaskCompleted`, `NoteCreated`, `EmailSent` events → `Contact::where('id', $contactId)->update(['last_contacted_at' => now()])`. Alternatively use a single `UpdateLastContactedAt` action class.

- **Unified Contact Timeline — event sources** (contact record timeline, Step 1):
  The timeline on `/contacts/{id}` is a **single chronological feed** built from a Union query across:

  | Event Type | Source Table | Filter | Icon |
  |---|---|---|---|
  | Note | `notes` | `noteable_type=App\\Models\\Contact AND noteable_id=$id` | pencil |
  | Email | `sent_mails` (backstage/laravel-mails) | `mailable_to LIKE $email` | envelope |
  | Task | `tasks` | `attached_contact_id=$id OR assigned_contact_id=$id` | check-circle |
  | Stage change | `activity_log` | `subject_type=Contact AND subject_id=$id AND description='updated'` (properties.stage changed) | arrow-right |
  | Reservation | `property_reservations` | `primary_contact_id=$id` | home |
  | System | `activity_log` | `subject_type=Contact AND subject_id=$id` (general audit events) | info-circle |

  Query: `SELECT 'note' as type, content as body, created_at FROM notes WHERE noteable_type=? AND noteable_id=? UNION SELECT 'email' as type, subject as body, sent_at as created_at FROM sent_mails WHERE mailable_to LIKE ? UNION ... ORDER BY created_at DESC LIMIT 50`.

  Frontend filter chips (All / Email / Call / Note / Task / Property) map to the `type` column.

  **Implementation tip**: Use a `ContactTimeline` action class that runs the union query and returns a sorted collection. Cache per contact for 60 seconds.

- **Smart List persistence** — database table (not localStorage):

  ```sql
  CREATE TABLE smart_lists (
      id BIGSERIAL PRIMARY KEY,
      user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      organization_id BIGINT NULL REFERENCES organizations(id) ON DELETE CASCADE,
      name VARCHAR(100) NOT NULL,
      filter_state JSONB NOT NULL DEFAULT '{}',
      sort_order INT DEFAULT 0,
      created_at TIMESTAMPTZ DEFAULT NOW(),
      updated_at TIMESTAMPTZ DEFAULT NOW()
  );
  CREATE INDEX idx_smart_lists_user ON smart_lists(user_id);
  ```

  Migration: `$table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); $table->string('name', 100); $table->jsonb('filter_state')->default('{}'); $table->integer('sort_order')->default(0); $table->timestamps();`

  UI: Click "+" in Smart List sidebar → modal with "List Name" input → POST `/smart-lists` → stored in `smart_lists`. Sidebar reads `SmartList::where('user_id', auth()->id())->orderBy('sort_order')`. Count badges: each SmartList runs its `filter_state` against the ContactDataTable base query — cache result for 5 minutes per list.

- **`Contact::toSearchableArray()` implementation**:

  ```php
  public function toSearchableArray(): array
  {
      return [
          'id'                 => $this->id,
          'full_name'          => trim("{$this->first_name} {$this->last_name}"),
          'email'              => $this->contactEmails->pluck('value')->implode(' '),
          'phone'              => $this->contactPhones->pluck('value')->implode(' '),
          'suburb'             => $this->extra_attributes?->suburb ?? '',
          'stage'              => $this->stage,
          'contact_origin'     => $this->contact_origin,
          'lead_score'         => $this->lead_score ?? 0,
          'tags'               => $this->tags->pluck('name')->implode(' '),
          'organization_id'    => $this->organization_id,
          'is_archived'        => (bool) $this->is_archived,
          'created_at'         => $this->created_at?->timestamp,
      ];
  }
  ```

  Typesense collection schema (in `config/scout.php` or Typesense config):

  ```php
  'contacts' => [
      'fields' => [
          ['name' => 'id',              'type' => 'string'],
          ['name' => 'full_name',       'type' => 'string'],
          ['name' => 'email',           'type' => 'string'],
          ['name' => 'phone',           'type' => 'string'],
          ['name' => 'suburb',          'type' => 'string'],
          ['name' => 'stage',           'type' => 'string', 'facet' => true],
          ['name' => 'contact_origin',  'type' => 'string', 'facet' => true],
          ['name' => 'lead_score',      'type' => 'int32'],
          ['name' => 'tags',            'type' => 'string'],
          ['name' => 'organization_id', 'type' => 'int64',  'facet' => true],
          ['name' => 'is_archived',     'type' => 'bool',   'facet' => true],
          ['name' => 'created_at',      'type' => 'int64'],
      ],
      'default_sorting_field' => 'created_at',
  ],
  ```

- **Contact type suggestion (recommended)**: During or after import, use Laravel AI SDK or Prism to suggest contact type from first_name, last_name, company_name (e.g. "lead" vs "partner"). Fallback: use is_partner and existing flags. Store suggestion for review or auto-apply when confidence is high.
- **Smart List AI suggestions**: After saving a manual smart list filter, HasAi `suggest` handler proposes additional filter refinements based on the result set characteristics. Rendered as dismissible chips via Thesys C1.
- **Contact enrich**: HasAi `enrich` handler on email column → Prism (OpenRouter) fills in missing job title or company from public sources (if `AiToolsFeature` enabled). Show preview of changes before applying.
- **Duplicate detection AI**: On email blur, POST `/contacts/check-duplicate`; also run embedding-based fuzzy match post-import (same person, two contacts) using pgvector similarity.
- *More ideas (enrichment, smart stage):* **13-ai-native-features-by-section.md** § Step 1.

## Verification (verifiable results, data fully imported)

- After import, verify row counts against your MySQL replica (see **10-mysql-usage-and-skip-guide.md**): **contacts** = 9,678 (leads count), **contact_emails** + **contact_phones** ≈ 20,074 (contactable rows), **sources** = 17, **companies** = 969. Ensure every lead_id has a contact_id in the map; no orphan contact_emails/contact_phones. Implement `php artisan fusion:verify-import-contacts` to compare counts and map completeness. Full checklist: **11-verification-per-step.md** § Step 1.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 2 until the human has completed the checklist below.**

Human must:
- [ ] Confirm migrations for contacts, contact_emails, contact_phones (and sources, companies) ran.
- [ ] Confirm `fusion:import-contacts` completed without fatal errors.
- [ ] Confirm verification PASS (e.g. `fusion:verify-import-contacts` or manual count: contacts = 9,678, contact details ≈ 20,074).
- [ ] Confirm lead_id→contact_id map is persisted (e.g. `contacts.legacy_lead_id` or import_mappings table) for use in later steps.
- [ ] Optionally spot-check a few contacts in Filament/UI for correct data.
- [ ] Approve proceeding to Step 2 (users and contact link).

## Acceptance Criteria

- [ ] Migrations for contacts, contact_emails, contact_phones (and sources, companies) run.
- [ ] Contact model has relationships, userstamps, activity logging.
- [ ] CRM roles/permissions seeded (or run sync:route-permissions when routes are in place).
- [ ] Import command `fusion:import-contacts` runs and populates contacts + contact_emails/contact_phones from MySQL leads + contactable contacts; lead_id→contact_id map persisted for next steps.
- [ ] Verification command or manual count check confirms data fully imported (contacts = 9,678, contact details ≈ 20,074).
- [ ] All 14 contact stage state classes exist in `app/States/Contact/`; `ContactStage` abstract base registered in Contact model.
- [ ] Contact list supports search (name, email, phone), filter by stage, source, assigned_agent; bulk actions (assign, tag, export, archive) functional.
- [ ] `contacts.lead_score` column exists (nullable smallint); `contacts.last_contacted_at` column exists (nullable timestamp); both display correctly as colored badges in DataTable.
- [ ] `php artisan models:audit` passes for Contact, ContactEmail, ContactPhone models.
- [ ] Scout import run: `php artisan scout:import "App\Models\Contact"` succeeds; Typesense `contacts` collection exists with `name`, `email`, `phone`, `suburb`, `stage`, `tags` fields indexed.

**UI acceptance criteria (verified by Visual QA protocol — Task 3 checks):**
- [ ] `/contacts` loads with Smart List sidebar (220px, border-r, collapsible groups with count badges)
- [ ] ContactDataTable renders with lead_score badge column and color-coded last_contacted_at column
- [ ] `/contacts/{id}` uses **unified timeline** (no tab bar — Visual QA explicitly checks for absence of `[role=tablist]`)
- [ ] Timeline filter chips visible above feed (All / Email / Call / Note / Task / Property)
- [ ] RHS summary panel visible with stage dropdown + days-since-contact badge
- [ ] lead_score badge visible in contact record header
- [ ] Create Contact slide-over opens from "New Contact" button; all 5 sections render; duplicate email check fires on blur
