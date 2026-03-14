# Step 3: Projects and Lots

## Goal

Add **projects** and **lots** tables to the new app with organization_id and userstamps. No lead_id. Import projects and lots from MySQL.

## Suggested implementation order (use kit generators first)

1. **Generate models** with `make:model:full` for each entity below; then add plan-specific columns (organization_id, userstamps, all fields per Deliverables) to migrations and models.
2. **Run** `make:filament-resource Project --generate --view` and `make:filament-resource Lot --generate --view` (or Lot as relation manager on Project); `make:data-table Project` and `make:data-table Lot` (optional).
3. **Implement** `fusion:import-projects-lots` and `fusion:verify-import-projects-lots`.

**Models to generate:** Project, Lot, Developer, Projecttype, State, Suburb, PotentialProperty, ProjectUpdate, SprRequest, FlyerTemplate, Flyer. Example:
```bash
for m in Project Lot Developer Projecttype State Suburb; do php artisan make:model:full $m --migration --factory --seed --category=development; done
php artisan make:model:full PotentialProperty --migration --factory --seed --category=development
php artisan make:model:full ProjectUpdate --migration --factory --seed --category=development
php artisan make:model:full SprRequest --migration --factory --seed --category=development
php artisan make:model:full FlyerTemplate --migration --factory --seed --category=development
php artisan make:model:full Flyer --migration --factory --seed --category=development
```
Then edit migrations to add full column lists and organization_id, userstamps per Deliverables.

## Starter Kit References

- **Database**: `docs/developer/backend/database/README.md`, `docs/developer/backend/database/seeders.md`
- **Userstamps**: `docs/developer/backend/userstamps.md`
- **Activity log**: `docs/developer/backend/activity-log.md` — add LogsActivity to Project, Lot
- **DataTable**: `docs/developer/backend/data-table.md` — ProjectDataTable, LotDataTable
- **Actions/Controllers**: `docs/developer/backend/actions/README.md`, `docs/developer/backend/controllers/README.md`
- **Filament**: `docs/developer/backend/filament.md` — Project and Lot resources

## Deliverables

1. **Migrations**
   - **projects**: id, organization_id (nullable, FK → organizations), **legacy_id** (bigint, nullable, indexed — MySQL projects.id), title, stage, estate, total_lots, storeys, min/max landsize, living_area, bedrooms/bathrooms (or min/max), garage, min/max/avg rent, rent_yield, is_hot_property, description, **description_summary** (text, nullable — AI-generated 2-sentence summary; see AI Enhancements), min/max/avg price, body_corporate_fees, rates_fees, is_archived, is_hidden, start_at, end_at, is_smsf, is_firb, is_ndis, is_cashflow_positive, build_time, historical_growth, land_info, developer_id (nullable), projecttype_id (nullable),
     **lat** (decimal 10,8, nullable), **lng** (decimal 11,8, nullable) — for Map View; use devrabiul/laravel-geo-genius to geocode suburb/postcode during import or on save,
     **is_featured** (boolean, default false), **featured_order** (int, nullable) — for Featured Projects widget on dashboard,
     **is_co_living** (boolean, default false), **is_high_cap_growth** (boolean, default false), **is_rooming** (boolean, default false), **is_rent_to_sell** (boolean, default false), **is_exclusive** (boolean, default false) — additional filter flags confirmed on live site,
     timestamps, created_by, updated_by. Indexes: organization_id, stage, legacy_id, is_featured, lat/lng (for geo queries).
   - **lots**: id, project_id (FK → projects), **legacy_id** (bigint, nullable, indexed — MySQL lots.id), title, land_price, build_price, stage, level, building, floorplan, car, storage, view, garage, aspect, internal, external, total, storyes, land_size, title_status, living_area, price, bedrooms, bathrooms, study, mpr, powder_room, balcony, rent_yield, weekly_rent, rates, body_corporation, is_archived, is_nras, is_smsf, is_cashflow_positive, completion, timestamps, soft_deletes, created_by, updated_by. Indexes: project_id, legacy_id.

2. **Models**
   - **Project**: Fillable, casts (use **akaunting/laravel-money** for price fields per 00-database-design), Userstamps, LogsActivity, BelongsToOrganization (if used). **Scout**: add Searchable + toSearchableArray(); Typesense collection. **Slugs**: add **HasSlug** (spatie/laravel-sluggable) for website URLs (campaign websites, Step 21 API). **Soft cascade**: use **askedio/laravel-soft-cascade** for **lots** (soft-delete lots when Project is soft-deleted). Relationships: organization, lots, developer, projecttype, createdBy, updatedBy.
   - **Lot**: Fillable, casts (Money for price fields), SoftDeletes, Userstamps, LogsActivity. **Scout**: add Searchable. **Slugs**: add **HasSlug** (spatie/laravel-sluggable). Relationships: project, createdBy, updatedBy.

3. **Lookup and related tables**
   - **developers**, **projecttypes**, **states**, **suburbs**: Migrations (id, organization_id, name, …). developers.user_id (optional) or organization_id.
   - **potential_properties**: id, organization_id, title, suburb, state, developer_name (string, nullable), description (text, nullable), estimated_price_min (decimal, nullable), estimated_price_max (decimal, nullable), status (enum: evaluating|approved|rejected, default evaluating), imported_from_csv (boolean, default false), csv_row_data (json, nullable), created_by (FK → users), timestamps. **Promote to Project** action: single Filament/Inertia action that creates a new `Project` from PotentialProperty data (maps title, suburb, state, developer_name → developer lookup or create, description). Index: organization_id, status, state.
   - **project_updates**: id, project_id, user_id, content or similar, timestamps.
   - **special_property_requests** (SPR): id, user_id (FK → users — the subscriber who requested), title, description (text), state (string, nullable — filter for the request), spr_price (decimal 10,2, default 55.00), payment_status (enum: pending|paid|failed, default pending), payment_transaction_id (string, nullable), payment_access_code (string, nullable — eWAY), request_status (enum: pending|in_progress|completed, default pending), completed_by (FK → users, nullable), completed_at (timestamp, nullable), notes (text, nullable), created_by (FK → users), timestamps, soft_deletes. Index: user_id, request_status, payment_status. (Legacy table was `spr_requests`; map columns accordingly during import.)
   - **flyer_templates**: id, organization_id, name, … **flyers**: id, template_id, project_id, lot_id, poster_img_id, floorplan_img_id, notes, is_custom, timestamps, soft_deletes.
   - **user_project_favourites** (pivot): user_id (FK → users), project_id (FK → projects), created_at. Unique(user_id, project_id). No model needed — use `Project::belongsToMany(User::class, 'user_project_favourites')` and `User::belongsToMany(Project::class, 'user_project_favourites')`.
   - **Flyer PDF generation:** Use **[Spatie Laravel PDF](https://spatie.be/docs/laravel-pdf/v2/introduction)** (v2) for rendering flyer PDFs — not PDFCrowd or Browsershot as primary. Blade/views or programmatic builders feed into Laravel PDF; align with kit conventions.
   - **Optional:** **spatie/eloquent-sortable** for project list sort order if kit uses it (see 00-kit-package-alignment.md P3).

4. **Seeders**
   - Development/Production seeders for Project and Lot (optional); use `make:model:full` pattern.

5. **Filament**
   - Project resource (list, form, relation manager for lots). Lot resource or embedded in Project form.

6. **DataTables & Inertia**
   - ProjectDataTable, LotDataTable — add **HasAi** (NLQ, column insights, optional Thesys Visualize; see 00-kit-package-alignment.md).
   - **Projects page — grid-first layout** (see `00-ui-design-system.md` §7): Grid view is the **default** (client presentation). Property cards: hero photo, project name, suburb + state, developer, price-from, stage dot, available/reserved/sold counts. Table view is secondary (inventory/admin). **Use the DataTable built-in multi-layout switcher** (Table / Grid / Cards / Kanban) instead of a custom `ToggleGroup` — the package now includes these built-in. Map View (Leaflet) remains a separate 3rd mode alongside the DataTable switcher. Filter bar: suburb, price range, stage, developer. HasAi NLQ bar at top.
   - **Lot slide-over panel**: "View Lots" on a project card opens a right-side drawer. **Use `tableDetailDisplay(): 'drawer'` on the LotDataTable** — this is now built into the package (no need for a custom `<Sheet>` wrapper). The drawer shows lot inventory: lot number, floor, bed/bath/car, size (m²), price (Money formatted), status dot, Reserve action button inline. No page navigation — agent stays on the project grid while presenting to a client.
   - **Suburb/location:** Kit has **devrabiul/laravel-geo-genius**; use for suburb/postcode validation or enrichment when importing 15,299 suburbs and for address data in Step 5.

7. **Import command**
   - `php artisan fusion:import-projects-lots`. Source: MySQL `projects`, `lots`, `developers`, `projecttypes`, `states`, `suburbs`, `potential_properties`, `project_updates`, `spr_requests`, `flyers`, `flyer_templates`. Target: PostgreSQL tables. Map organization_id. Preserve or map developer_id, projecttype_id. project_updates and spr_requests: user_id, project_id (map project id if changed). Flyers: template_id, project_id, lot_id (map if needed).

## DB Design (this step)

- New tables: **projects**, **lots**, **developers**, **projecttypes**, **states**, **suburbs**, **potential_properties**, **project_updates**, **spr_requests**, **flyers**, **flyer_templates**. See `00-database-design.md` § 3.2, 3.6.

## Data Import

- **Source**: MySQL tables above.
- **Target**: PostgreSQL tables. Direct column mapping; organization_id from config. Build project_id/lot_id maps if IDs change for use in flyers and later steps.

## UI Specification

### Projects Page — Grid-First Layout

Default layout is **grid view** (card grid, 2–4 columns responsive). Table view is secondary (inventory/admin). View toggle persisted per user (session + DB via `persistLayout: 'db'`).

**Project Card component** (220–280px wide):
```
┌─────────────────────────┐
│  [Hero photo 16:9]      │  fallback: gradient tile with project initials
│  stage dot (top-right)  │
├─────────────────────────┤
│  Project Name (semibold)│
│  Suburb, State (muted)  │
│  Developer name (xs)    │
│  From $XXX,XXX          │
│  ● 12 avail  ◉ 3 res   │  green/amber dots
│  [View Lots]            │  → opens Lot Slide-over Sheet
└─────────────────────────┘
```

Stage dot colors: pre_launch=blue, selling=green, completed=gray, archived=red.

**Filter bar** (above grid): suburb search, price range slider, stage chips, developer dropdown. HasAi NLQ bar at top (natural language: "Show me SMSF-eligible projects in Brisbane under $600k").

**View toggle**: Grid | Table | **Map** (3rd mode — confirmed on live site). Toggle persisted per user (`persistLayout: 'db'`).

### Map View (3rd Layout Mode — Confirmed from Live v3)

> **Source**: Live `/projects` has a Grid | Table | Map 3-mode switcher. Map is Leaflet.js with lat/lng markers.

**Package**: `leaflet` + `react-leaflet` v4 + `@types/leaflet` — add via bun (NOT in starter kit package.json).

```bash
bun add leaflet react-leaflet @types/leaflet
```

**React component** `<ProjectMapView>`:
- Full-height map container (fills viewport below header)
- Marker per project at `projects.lat`/`projects.lng` (skip projects where lat/lng is null)
- Click marker → slide-over with the same project card component used in grid view
- Marker cluster at high zoom-out levels (use `react-leaflet-cluster` if desired)
- **Radius search**: km slider (1–100) + suburb/postcode autocomplete (devrabiul/laravel-geo-genius) → POST `/projects/map-search?lat=&lng=&radius_km=` → returns filtered project ids → updates both visible markers AND DataTable results (shared query state via Inertia partial reload)

**Geocoding on import/save**: During `fusion:import-projects-lots`, for each project where lat/lng is null, attempt to geocode from `suburb` + `state` using laravel-geo-genius. Store in `projects.lat`/`projects.lng`. Add a `projects:geocode-missing` artisan command for re-running.

**`Project::toSearchableArray()` implementation**:

```php
public function toSearchableArray(): array
{
    return [
        'id'                  => $this->id,
        'title'               => $this->title,
        'suburb'              => $this->suburb ?? '',
        'state'               => $this->state ?? '',
        'developer'           => $this->developer?->name ?? '',
        'stage'               => $this->stage ?? '',
        'description_summary' => $this->description_summary ?? '',
        'min_price'           => (float) ($this->min_price ?? 0),
        'max_price'           => (float) ($this->max_price ?? 0),
        'is_featured'         => (bool) $this->is_featured,
        'is_hot_property'     => (bool) $this->is_hot_property,
        'is_archived'         => (bool) $this->is_archived,
        'organization_id'     => $this->organization_id,
        'location'            => ($this->lat && $this->lng)
            ? ['lat' => (float) $this->lat, 'lng' => (float) $this->lng]
            : null, // Typesense geopoint field — null excluded from index
        'created_at'          => $this->created_at?->timestamp,
    ];
}
```

Typesense collection fields include `location` as `geopoint` type for radius search.

**`Lot::toSearchableArray()` implementation**:

```php
public function toSearchableArray(): array
{
    return [
        'id'            => $this->id,
        'title'         => $this->title ?? "Lot {$this->id}",
        'project_id'    => $this->project_id,
        'project_title' => $this->project?->title ?? '',
        'bedrooms'      => (int) ($this->bedrooms ?? 0),
        'bathrooms'     => (int) ($this->bathrooms ?? 0),
        'car'           => (int) ($this->car ?? 0),
        'price'         => (float) ($this->price ?? 0),
        'title_status'  => $this->title_status ?? 'available',
        'is_smsf'       => (bool) $this->is_smsf,
        'is_archived'   => (bool) $this->is_archived,
        'organization_id' => $this->project?->organization_id,
    ];
}
```

**Typesense geo-search**: `Project::toSearchableArray()` includes `location` as a `geopoint` field for Typesense geo-radius search support.

**ProjectDataTable `layoutSwitcher`**: Updated to `['grid', 'table', 'map']` with `'defaultLayout' => 'grid'`.

### Lot Slide-over Panel (`<Sheet side="right">`, 480px)

- Header: Project name + close button
- Filter chips: bed/bath/floor/status (Available | Reserved | Sold)
- Table: lot#, level, bed/bath/car, size m², price (Money), status dot, [Reserve] action button
- Reserve inline → opens mini reservation form (contact search + deposit amount + terms checkbox)
- No page navigation during slide-over — agent stays on project grid while presenting to a client

### Create/Edit Project Form (wide slide-over or full-page, tabbed)

```
Tab 1 — Basic Info
  Title* | Stage* (select) | Developer (searchable) | Project Type
  Description (Tiptap rich text) | Start/End dates

Tab 2 — Lot Statistics
  Total Lots | Storeys | Min/Max Landsize | Living Area
  Bedrooms/Bathrooms/Garage | Min/Max/Avg Price | Rent Yield

Tab 3 — Flags & Compliance
  Hot Property toggle | SMSF / FIRB / NDIS / Cashflow Positive toggles
  Body Corporate Fees | Rates | Build Time | Historical Growth

Tab 4 — Media
  Hero photo upload | Gallery (if media module available)
```

Save: close form, flash toast, grid refreshes via Inertia partial reload.

**Stage suggestion**: After typing description, HasAi `suggest` handler calls Prism → proposes stage inline ("Looks like Pre-Launch — confirm?"). One-click accept.

### Project Detail Page (full-page, tabbed)

Tabs: **Lots** (embedded LotDataTable) | **Activity** (timeline) | **Flyers** (FlyerDataTable) | **SPR Requests** | **Updates** | **Contacts Matched** (contacts whose preferences match this project's criteria — uses pgvector similarity from buyer–lot matching agent).

### Standalone Lot List — Inventory View

Accessible from sidebar nav or Project detail "Lots" tab. Table-first layout for inventory management.

**Columns**: lot#, project (filterable, hidden in project-scoped view), floor/level, bed/bath/car, size, price, status, last updated, actions.

**Status filter tabs (Quick Views)**: Available | Reserved | Sold | All

### Create/Edit Lot Form (slide-over, single-column)

```
Lot Number* | Title | Stage/Level | Building
Floor Plan | Bed/Bath/Car/Study/Balcony
Land Size | Internal/External/Total area
Price* | Land Price | Build Price | Weekly Rent
Status (title_status: available/reserved/sold)
Flags: NRAS / SMSF / Cashflow Positive
Completion date
```

### DataTable Configuration — ProjectDataTable

**Traits**: `HasExport, HasSelectAll, HasToggle, HasAi, HasReorder`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `title` | `text` | searchable, linkTo project |
| `suburb` | `text` | searchable, filterable |
| `state` | `badge` | filterable |
| `developer.name` | `text` | filterable (relation) |
| `stage` | `badge` | colorMap |
| `min_price` | `money` | sortable |
| `available_lots_count` | `number` | computed |
| `reserved_lots_count` | `number` | computed |
| `sold_lots_count` | `number` | computed |
| `is_hot_property` | `toggle` | HasToggle |
| `is_archived` | `toggle` | HasToggle |
| `start_at` | `date` | sortable |

**Options**:
```php
'layoutSwitcher' => ['grid', 'table', 'map'], // map = Leaflet react-leaflet view
'defaultLayout' => 'grid',
'persistLayout' => 'db', // per-user via DB, survives device switches
'globalSearch' => true,
'columnFilters' => true,
'groupBy' => ['stage', 'developer'],
'savedViews' => true,
'bulkActions' => ['archive', 'export'],
'rowClickAction' => 'navigate',
```

**`tableQuickViews()`**:
```php
return [
    QuickView::make('all')->label('All')->default(),
    QuickView::make('available')->label('Available')->filter(['is_archived' => false]),
    QuickView::make('hot')->label('Hot Properties')->filter(['is_hot_property' => true]),
    QuickView::make('pre_launch')->label('Pre-Launch')->filter(['stage' => 'pre_launch']),
    QuickView::make('completed')->label('Completed')->filter(['stage' => 'completed']),
];
```

**`tableAnalytics()`**:
```php
return [
    Analytic::make('total')->label('Total Projects')->query(fn($q) => $q->count()),
    Analytic::make('available_lots')->label('Available Lots')->query(
        fn($q) => $q->withCount(['lots' => fn($l) => $l->where('title_status', 'available')])->sum('lots_count')
    ),
    Analytic::make('hot')->label('Hot Properties')->query(fn($q) => $q->where('is_hot_property', true)->count()),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a real estate project inventory for a property sales agency. Projects are
residential developments (apartments, houses, land). Key fields: stage (pre_launch/selling/completed),
available/reserved/sold lot counts, price range, developer, suburb/state.
Help agents identify projects to present to specific buyer profiles.';
```

### DataTable Configuration — FlyerDataTable

**Traits**: `HasExport, HasToggle, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `id` | `text` | label "#" |
| `project.title` | `text` | searchable, filterable, linkTo project |
| `lot.title` | `text` | filterable (nullable — some flyers are project-level) |
| `flyerTemplate.name` | `text` | filterable |
| `created_by_user.name` | `avatar_text` | filterable |
| `is_custom` | `toggle` | HasToggle |
| `created_at` | `date` | sortable |

**Options**:
```php
'globalSearch' => true,
'columnFilters' => true,
'bulkActions' => ['export', 'archive'],
'rowClickAction' => 'slideOver', // opens PDF preview
```

**`tableQuickViews()`**:
```php
return [
    QuickView::make('all')->label('All Flyers')->default(),
    QuickView::make('custom')->label('Custom Flyers')->filter(['is_custom' => true]),
    QuickView::make('template')->label('Template-Based')->filter(['is_custom' => false]),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a property flyer library for a real estate agency. Flyers are PDF marketing
materials linked to projects and optionally specific lots. Key fields: project, lot (nullable),
template used, is_custom (user-modified vs standard). Help identify missing flyers or flyer coverage.';
```

### DataTable Configuration — LotDataTable

**Traits**: `HasExport, HasToggle, HasSelectAll, HasInlineEdit, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `title` | `text` | searchable |
| `project.title` | `text` | filterable (hidden in project-scoped view) |
| `level` | `text` | filterable |
| `bedrooms` | `number` | filterable (range) |
| `bathrooms` | `number` | filterable |
| `car` | `number` | filterable |
| `internal` | `number` | label "Internal m²" |
| `total` | `number` | label "Total m²" |
| `price` | `money` | sortable, filterable (range) |
| `title_status` | `badge` | colorMap: available=green, reserved=amber, sold=red |
| `weekly_rent` | `money` | hidden by default |
| `rent_yield` | `percentage` | hidden by default |
| `is_archived` | `toggle` | HasToggle |

**Options**:
```php
'layoutSwitcher' => ['table', 'card'],
'columnFilters' => true,
'stickyHeader' => true,
'bulkActions' => ['reserve', 'archive', 'export'],
'inlineEditColumns' => ['price', 'weekly_rent', 'title_status'],
'rowClickAction' => 'slideOver', // opens Lot detail Sheet
```

**`tableQuickViews()`**:
```php
return [
    QuickView::make('available')->label('Available')->default()->filter(['title_status' => 'available', 'is_archived' => false]),
    QuickView::make('reserved')->label('Reserved')->filter(['title_status' => 'reserved']),
    QuickView::make('sold')->label('Sold')->filter(['title_status' => 'sold']),
    QuickView::make('all')->label('All'),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a lot/unit inventory for a property development project. Each lot has
bed/bath/car counts, size in m², price, and a status (available/reserved/sold).
Help agents identify lots that match specific buyer criteria (budget, size, bedrooms).';
```

### Favourites (Confirmed from Live v3 — `/favourites`)

**Heart/star toggle** button on every project card in grid view. Optimistic toggle via Inertia POST.

**Project model**:
```php
public function favouritedByUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'user_project_favourites');
}

public function isFavouritedBy(User $user): bool
{
    return $this->favouritedByUsers()->where('user_id', $user->id)->exists();
}
```

**Route**: `POST /projects/{project}/favourite` → toggle pivot row. Return `{ is_favourited: bool }` for optimistic UI update.

**`/favourites` page**: ProjectDataTable scoped to `auth()->user()->favouriteProjects()` (via `User::belongsToMany(Project::class, 'user_project_favourites')`). Same grid/table/map view toggle. No special DataTable — reuse ProjectDataTable with a scope applied.

---

### Featured Projects (Confirmed from Live v3 — `/featured_projects`)

**Admin management page** (`/admin/featured-projects`):
- List of all `is_featured = true` projects
- Drag-to-reorder via `featured_order` column (use spatie/eloquent-sortable if kit uses it; otherwise manual `featured_order` int)
- HasToggle on `is_featured` in ProjectDataTable
- "Add to Featured" action on any project record

**Dashboard widget**: "Featured Properties" — grid of up to 6 projects where `is_featured = true`, ordered by `featured_order ASC`. Shown to all authenticated users (subscribers see it in their portal; agents see it on their dashboard).

**Subscriber portal**: Featured projects also highlighted in the subscriber-facing `/projects` page with a "Featured" ribbon/badge on the card.

---

### Potential Properties (Confirmed from Live v3 — `/potential-properties`)

Full staging area for projects being evaluated before going live.

**Page** (`/potential-properties`):
- DataTable with `HasImport` (CSV upload), `HasExport`, filter bar by state
- CSV import columns: title, suburb, state, developer_name, price_min, price_max, description (maps to PotentialProperty columns)
- **State filter chips**: All | NSW | VIC | QLD | SA | WA | ACT | NT | TAS
- Row actions: Edit (slide-over), **Promote to Project** (single action → creates Project, redirects to project edit)
- "Promote to Project" flow:
  1. Creates `Project` with: title, suburb (→ geocode lat/lng), developer (find or create), description from PotentialProperty
  2. Sets `potential_property.status = 'approved'`
  3. Redirects to new `/projects/{id}/edit` so agent completes remaining fields

**Create/Edit Potential Property Form** (slide-over):
```
Title* | Suburb | State | Developer Name
Estimated Price Min | Estimated Price Max
Description (textarea) | Status (evaluating/approved/rejected)
```

---

### Special Property Request (SPR) — Full Spec (Confirmed from Live v3 — `/spr-history`)

> **Source**: Live `/spr-history` — paid service; subscribers pay $55/request for custom property research. Separate from automated matching.

**Model**: `SpecialPropertyRequest` (table `special_property_requests`).

**Subscriber page** (`/spr`) — Submit a new request:
```
Request Title* | Description* (textarea — describe what property they're looking for)
State Filter (optional — which state/s to search in)
Price: $55.00 (displayed, not editable)
[Submit & Pay] → eWAY hosted redirect (same pattern as reservation deposits)
```

On eWAY return → payment_status updated → request_status = pending → admin notified.

**Subscriber page** (`/spr-history`) — History list:
- Table: title, submitted date, request_status badge (Pending / In Progress / Completed), payment_status badge
- Click row → slide-over with request detail + admin notes (read-only for subscriber)

**Admin page** (`/admin/spr`) — Manage all requests:
- DataTable: user, title, description, state, submitted_at, payment_status, request_status, assigned to, actions
- Quick Views: Paid + Pending | In Progress | Completed | All
- Row actions: Mark In Progress | Mark Completed (sets completed_by = auth user, completed_at = now) | Add Note
- Bulk actions: Mark Completed (batch)

**Reverb broadcast**: `SprPaymentReceived` — broadcast on admin channel when eWAY confirms payment. Triggers toast for admin and refreshes SPR DataTable.

**eWAY integration**: Use same `EwayIntegration` Saloon connector as Step 4 reservations. `spr_price` = 55.00 default but configurable via `config/spr.php` or org setting.

---

## AI Enhancements

- **Description summary**: On project save (or background job), Prism generates a 2-sentence summary stored in `projects.description_summary`. Used in property cards where full description is too long.
- **Stage suggestion**: On new project create, if description is provided → Prism classifies stage from text. Shown as inline suggestion ("Looks like Pre-Launch — confirm?"). Rendered via HasAi `suggest`.
- **Buyer–Lot matching**: `MatchBuyerToLotsJob` — for a contact, find lots where bed/bath/budget overlap → rank by pgvector similarity (embedding of contact preferences vs lot description). Agent tool `lots_match_for_contact(contact_id)` callable from AI assistant. C1 renders matching `PropertyCard` list.
- **Similar projects**: Given a project, find similar projects via `nearestNeighbors()` on project description embeddings. Shown in "You may also like" section of project detail page.
- **Auto-tagging**: Suggest tags (e.g. "SMSF", "FIRB", "Cashflow Positive") from project description. Prism structured output → shown as one-click chip suggestions in the form.
- **Typesense**: `Project::toSearchableArray()` → title, suburb, stage, developer, description_summary. `Lot::toSearchableArray()` → title, project, bed/bath/price/status. Powers instant search in all picker dropdowns.
- *More ideas (lot recommendation per buyer):* **13-ai-native-features-by-section.md** § Step 3.

## Verification (verifiable results, data fully imported)

- After import, verify row counts against MySQL replica: **projects** = 15,381, **lots** = 120,785, **developers** = 332, **projecttypes** = 12, **states** = 8, **suburbs** = 15,299, **project_updates** = 153,273, **flyers** = 10,147, **flyer_templates** = 3; lots.project_id all exist in projects. Implement `php artisan fusion:verify-import-projects-lots`. See **11-verification-per-step.md** § Step 3 and **10-mysql-usage-and-skip-guide.md** for baseline.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 1 until the human has completed the checklist below.**

Human must:
- [ ] Confirm all migrations (projects, lots, developers, projecttypes, states, suburbs, potential_properties, project_updates, spr_requests, flyers, flyer_templates) ran.
- [ ] Confirm `fusion:import-projects-lots` completed; verification PASS (expected counts match, e.g. projects = 15,381, lots = 120,785).
- [ ] Confirm lots reference valid projects (no orphan lots).
- [ ] Optionally spot-check a project and its lots in the UI.
- [ ] Approve proceeding to Step 1 (contacts and roles).

## Acceptance Criteria

- [ ] Migrations for projects, lots, developers, projecttypes, states, suburbs, potential_properties, project_updates, special_property_requests, flyers, flyer_templates run.
- [ ] `projects` table has `lat`, `lng`, `is_featured`, `featured_order`, `description_summary`, `is_co_living`, `is_high_cap_growth`, `is_rooming`, `is_rent_to_sell`, `is_exclusive` columns.
- [ ] `user_project_favourites` pivot table exists with UNIQUE(user_id, project_id) constraint.
- [ ] Models have relationships, userstamps, activity logging.
- [ ] Import command populates all tables from MySQL; lots correctly reference projects.
- [ ] Verification confirms expected row counts (e.g. projects = 15,381, lots = 120,785).
- [ ] Scout import run: `php artisan scout:import "App\Models\Project"` and `php artisan scout:import "App\Models\Lot"` succeed; Typesense collections exist with correct schema including `lat`, `lng` geo fields.

**UI acceptance criteria (verified by Visual QA protocol — Task 2 checks):**
- [ ] `/projects` renders in **grid layout by default** (not table), with project cards visible
- [ ] Project cards show hero photo (or gradient fallback), name, suburb/state, developer, price-from, stage dot, lot counts
- [ ] "View Lots" button on card opens right-side Sheet drawer (not a new page)
- [ ] Lot table inside Sheet shows lot#, bed/bath/car, size, price, status dot, Reserve button
- [ ] HasAi NLQ bar visible above the grid
- [ ] Grid / Table / **Map** view switcher visible (3 buttons); switching to Map renders `<ProjectMapView>` with Leaflet markers — no console errors
- [ ] Each project marker on map is clickable and opens a slide-over with project card details
- [ ] Radius search slider (1–100 km) filters visible markers and underlying DataTable simultaneously
- [ ] `/projects/{id}` loads with Lots tab showing embedded LotDataTable
- [ ] FlyerDataTable visible in Flyers tab of project detail (or at `/flyers`)
- [ ] Project create slide-over opens with 4 tabs; stage suggestion AI prompt appears after description input
- [ ] Heart icon on project card toggles favourite status optimistically (POST `/projects/{id}/favourite`); `/favourites` page shows user's saved projects
