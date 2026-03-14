# Component Map — Fusion CRM v4

Every Inertia page is a **React TSX component** rendered via `@inertiajs/react`. This document maps each key CRM page to its file path, layout wrapper, shadcn/ui components, and data props. Chief reads this in Step 0 and follows it for all page builds.

**Stack:** Laravel 12 · Inertia.js · React 19 · TypeScript · Tailwind v4 · shadcn/ui · Lucide React

**Important:** The HTML files in `rebuild-plan/ui-prototypes/` are **visual design references only** — used as QA targets for Playwright screenshot checks. Do NOT copy HTML from those files into React components. Instead use shadcn/ui components styled with the Fusion brand tokens from `00-ui-design-system.md`.

---

## File structure conventions

```
resources/js/
  Pages/
    Dashboard/       Index.tsx
    Contacts/        Index.tsx  Show.tsx  Create.tsx  Edit.tsx
    Projects/        Index.tsx  Show.tsx
    Lots/            Show.tsx   (slide-over inside Projects, not a standalone page nav)
    Reservations/    Index.tsx  Show.tsx
    Sales/           Index.tsx  Show.tsx
    Tasks/           Index.tsx
    Reports/         Index.tsx  Show.tsx
    Flyers/          Index.tsx  Show.tsx
    MailLists/       Index.tsx  Show.tsx
    BotInABox/       Index.tsx  Run.tsx
    Websites/        Index.tsx
    Settings/
      ApiKeys.tsx
  Components/
    Layout/
      AppLayout.tsx         ← main shell: sidebar + page-header slot
      PageHeader.tsx        ← breadcrumb + title + right-actions slot
      AppSidebar.tsx        ← 220px sidebar, collapsible to 56px icon-only
    Ui/                     ← shadcn generated components (do not edit directly)
    Shared/
      SmartListSidebar.tsx  ← contact list left panel
      DataTable.tsx         ← reusable table wrapper with HasAi NLQ + pagination
      StatusDot.tsx         ← 8px dot + label (never Badge)
      DaysSinceBadge.tsx    ← color-coded recency badge
      LeadScoreBadge.tsx    ← 0–100 score badge
      AiChatPanel.tsx       ← floating AI assistant (Sheet, 400px)
      ActivityTimeline.tsx  ← unified chronological feed
      MilestoneTimeline.tsx ← vertical step tracker (reservations/sales)
      ThesysC1Renderer.tsx  ← renders AI responses via @thesys/client (never plain <p>)
```

---

## App shell — `AppLayout.tsx`

Every CRM page wraps in `<AppLayout>`. Implemented once in Step 0.

```tsx
// resources/js/Components/Layout/AppLayout.tsx
import { AppSidebar } from './AppSidebar'
import { PageHeader } from './PageHeader'
import { AiChatPanel } from '../Shared/AiChatPanel'

export function AppLayout({ children, title, breadcrumbs, actions }: AppLayoutProps) {
  return (
    <div className="flex min-h-screen bg-background">
      <AppSidebar />
      <div className="flex-1 flex flex-col ml-[220px]">
        <PageHeader title={title} breadcrumbs={breadcrumbs} actions={actions} />
        <main className="flex-1 p-6">{children}</main>
      </div>
      <AiChatPanel />  {/* floating bottom-right, present on every page */}
    </div>
  )
}
```

---

## Sidebar — `AppSidebar.tsx`

```tsx
// resources/js/Components/Layout/AppSidebar.tsx
// Nav structure: Dashboard (flat) | Contacts▶ | Properties▶ | Sales▶ | Tasks (flat) | Marketing▶ | Reports▶ | Resources (flat) | Websites (flat) | --- | Settings | Admin Panel
// Expandable groups: useState per group key, open/close on click, chevron rotates
// Active parent: bg-primary/5 text-primary font-medium border-l-2 border-primary when any child is active
// Active child: text-primary font-medium, 5px dot bullet colored primary
// Collapsed (56px icon-only): icons only, Tooltip wrapping each item
// Logo: <img src="/images/logo/logo.png" alt="Fusion CRM" className="h-9 w-auto" />
// Icons (all Lucide React): LayoutDashboard, Users, Building2, TrendingUp, CheckSquare, Megaphone, BarChart3, FolderOpen, Globe, Settings, Shield, ChevronRight
```

---

## Page component map

### Dashboard — `Pages/Dashboard/Index.tsx`

**Route:** GET `/dashboard` → `DashboardController@index`

**Layout:** `<AppLayout title="Dashboard">`

**Key shadcn components:**
- `Card` — KPI stat cards (4 total)
- `Table` — Priority Work Queue (compact, `py-2.5` rows)
- `Button` — Quick Add, View All
- No `Sheet` on this page

**Data props (from controller via Inertia::render):**
```ts
{
  newLeadsCount: number,
  tasksDueTodayCount: number,
  staleContactsCount: number,
  priorityContacts: Contact[],   // max 10, sorted by days_since_contact desc
  pipelineFunnel: { stage: string; count: number }[],
  kpis: { totalContacts: number; activeReservations: number; commissionsDue: string; avgDaysToClose: number },
  aiInsight: string | null,      // pre-computed, nullable
}
```

**Component structure:**
```tsx
<AppLayout title="Dashboard">
  {/* Action row */}
  <div className="grid grid-cols-3 gap-4 mb-6">
    <ActionCard count={newLeadsCount} label="New Leads — No Contact" />
    <ActionCard count={tasksDueTodayCount} label="Tasks Due Today" />
    <ActionCard count={staleContactsCount} label="Stale Contacts (30+ days)" />
  </div>
  {/* Priority work queue */}
  <Card className="mb-6">
    <CardHeader>Priority Work Queue</CardHeader>
    <Table> {/* compact rows */} </Table>
  </Card>
  {/* Bento: pipeline funnel + AI insight */}
  <div className="grid grid-cols-2 gap-4 mb-6">
    <PipelineFunnelChart data={pipelineFunnel} />
    <AiInsightCard insight={aiInsight} />
  </div>
  {/* KPI row */}
  <div className="grid grid-cols-4 gap-4">
    <KpiCard /> {/* ×4 */}
  </div>
</AppLayout>
```

---

### Contact List — `Pages/Contacts/Index.tsx`

**Route:** GET `/contacts` → `ContactController@index`

**Layout:** `<AppLayout>` + `<SmartListSidebar>` (left panel, 220px)

**Key shadcn components:**
- `Table` (DataTable wrapper) — compact rows, 40px height
- `Input` — HasAi NLQ bar at top (full width)
- `DropdownMenu` — filter dropdowns
- `Checkbox` — row selection
- `Button` — New Contact, Export
- No `Sheet` on the list page itself

**Data props:**
```ts
{
  contacts: PaginatedContacts,     // Inertia paginated resource
  smartLists: SmartList[],         // left sidebar groups + counts
  activeList: string | null,       // currently selected smart list slug
  filters: ContactFilters,
}
```

**Component structure:**
```tsx
<AppLayout title="Contacts" actions={<Button>New Contact</Button>}>
  <div className="flex gap-0">
    <SmartListSidebar lists={smartLists} active={activeList} />
    <div className="flex-1 min-w-0">
      <HasAiNlqBar placeholder="Ask AI: 'Show hot leads from Facebook last 30 days'" />
      <FilterBar />
      <DataTable
        columns={contactColumns}
        data={contacts}
        rowHeight={40}
      />
    </div>
  </div>
</AppLayout>
```

---

### Contact Detail — `Pages/Contacts/Show.tsx`

**Route:** GET `/contacts/{contact}` → `ContactController@show`

**Layout:** `<AppLayout>` with 2-column layout (60/40)

**Key shadcn components:**
- `Button` — Send Email, Log Call, Add Note, Schedule Task, Move Stage
- `Sheet` — NOT used here (timeline is inline)
- `Textarea` — inline note entry in timeline
- No `Tabs` — unified timeline with filter chips (NOT tabbed)

**Data props:**
```ts
{
  contact: Contact & { lead_score: number; days_since_contact: number; stage: string },
  timeline: TimelineEvent[],       // all activity, newest first
  tasks: Task[],                   // upcoming tasks (right sidebar)
  propertyInterests: Lot[],        // matched lots
  aiSummary: string | null,
}
```

**Component structure:**
```tsx
<AppLayout title={contact.full_name}>
  <div className="grid grid-cols-[1fr_360px] gap-6">
    {/* LEFT: main content */}
    <div>
      <ContactHeader contact={contact} />  {/* avatar, name, stage dot, lead score, days-since */}
      <AiSummaryCard summary={aiSummary} />
      <ActivityTimeline events={timeline} />  {/* unified feed, filter chips at top, NO tabs */}
    </div>
    {/* RIGHT: sidebar */}
    <div className="space-y-4">
      <StageStepper contact={contact} />
      <UpcomingTasks tasks={tasks} />
      <PropertyInterests lots={propertyInterests} />
      <ContactInfoCard contact={contact} />
    </div>
  </div>
</AppLayout>
```

---

### Project List — `Pages/Projects/Index.tsx`

**Route:** GET `/projects` → `ProjectController@index`

**Layout:** `<AppLayout>`

**Key shadcn components:**
- `Card` — project card in grid view
- `Sheet` — lot slide-over (opens when "View Lots" clicked)
- `Table` — lot inventory inside slide-over
- `Input` — HasAi NLQ bar
- `Select` — filter dropdowns (suburb, price, stage, developer)
- `ToggleGroup` — grid/list view switch

**Data props:**
```ts
{
  projects: Project[],   // includes available_lots_count, reserved_lots_count, sold_lots_count
  filters: ProjectFilters,
  totals: { available: number; reserved: number; sold: number },
}
```

**Lot slide-over (no page navigation):**
```tsx
<Sheet open={open} onOpenChange={setOpen}>
  <SheetContent side="right" className="w-[640px] sm:max-w-[640px]">
    <SheetHeader><SheetTitle>{project.name} — Lots</SheetTitle></SheetHeader>
    <LotInventoryTable lots={lots} />
  </SheetContent>
</Sheet>
```

---

### Reservation Detail — `Pages/Reservations/Show.tsx`

**Route:** GET `/reservations/{reservation}` → `ReservationController@show`

**Key shadcn components:**
- `Separator` — between milestone steps
- No `Sheet` — milestone timeline is inline left column

**Milestone steps (6):** Enquiry → Qualified → Reservation → Contract → Unconditional → Settled

```tsx
<div className="grid grid-cols-[200px_1fr] gap-8">
  <MilestoneTimeline steps={milestoneSteps} current={reservation.stage} />
  <ReservationDetails reservation={reservation} />
</div>
```

---

### Tasks — `Pages/Tasks/Index.tsx`

**Route:** GET `/tasks` → `TaskController@index`

**Key shadcn components:**
- `Checkbox` — inline complete toggle per row
- `Badge` — priority (one of the few places a Badge is appropriate — priority levels, not status dots)
- `Table` — task rows grouped by Due Today / Overdue / Upcoming

---

### AI Chat Panel — `Components/Shared/AiChatPanel.tsx`

Present on every page (rendered in AppLayout). A `Sheet` fixed bottom-right.

```tsx
// Floating trigger: 56px circle, bg-primary, Sparkles icon, fixed bottom-6 right-6
// Sheet: side="right", width 400px
// Inside: role-aware suggested prompt chips, chat message list, input + send
// Responses rendered via <ThesysC1Renderer response={msg} /> — never plain <p>
```

---

## shadcn components — install checklist for Step 0

Chief must run `npx shadcn@latest add [component]` for each of these in Step 0 if not already in kit:

```bash
# Core layout
npx shadcn@latest add card sheet separator

# Navigation
npx shadcn@latest add breadcrumb

# Forms
npx shadcn@latest add button input textarea label select checkbox
npx shadcn@latest add form   # react-hook-form integration
npx shadcn@latest add combobox   # multi-select for tags/projects

# Feedback
npx shadcn@latest add badge toast sonner skeleton

# Data
npx shadcn@latest add table

# Overlay
npx shadcn@latest add dialog dropdown-menu popover tooltip

# Navigation
npx shadcn@latest add toggle-group tabs   # tabs only for admin panel, not CRM pages
```

After adding: update `components.json` base color = zinc, CSS vars = true, radius = 0.5rem.

---

## Logo

Logo file: `public/images/logo/logo.png` (copied from legacy `public/images/logo/logo.png`).

In `AppSidebar.tsx`:
```tsx
<img src="/images/logo/logo.png" alt="Fusion CRM" className="h-9 w-auto" />
```

In Filament (Step 0 — `config/filament.php` or `AdminPanelProvider`):
```php
->brandLogo(asset('images/logo/logo.png'))
->brandLogoHeight('36px')
```

---

## Inertia page rendering

Every page uses `Inertia::render()` — never blade views for CRM pages:

```php
// Example: ContactController@index
return Inertia::render('Contacts/Index', [
    'contacts' => ContactResource::collection($contacts->paginate(50)),
    'smartLists' => SmartListResource::collection($smartLists),
    'filters' => request()->only(['search', 'stage', 'agent', 'list']),
]);
```

Use `laravel/wayfinder` for type-safe route references in React:
```tsx
import { route } from '@/lib/utils'  // or wayfinder generated file
<Link href={route('contacts.show', contact.id)}>...</Link>
```

---

## TypeScript type conventions

```ts
// resources/js/types/index.d.ts
export interface Contact {
  id: number
  full_name: string
  email: string | null
  phone: string | null
  stage: 'new' | 'contacted' | 'qualified' | 'proposal' | 'under_contract' | 'converted' | 'archived'
  lead_score: number | null      // 0–100, null if not yet computed
  days_since_contact: number | null
  contact_origin: 'property' | 'saas_product'
  organization_id: number
  // ...
}

export interface Project {
  id: number
  name: string
  suburb: string
  state: string
  developer: string
  min_price: number | null
  stage: 'presales' | 'construction' | 'complete'
  available_lots_count: number
  reserved_lots_count: number
  sold_lots_count: number
  // ...
}
```

---

## Visual prototype → React implementation mapping

| Prototype file | Inertia component | Status |
|---|---|---|
| `ui-prototypes/dashboard.html` | `Pages/Dashboard/Index.tsx` | Visual reference — implement in Step 0 / Task 1 |
| `ui-prototypes/contacts.html` | `Pages/Contacts/Index.tsx` | Visual reference — implement in Step 1 / Task 3 |
| `ui-prototypes/contact-detail.html` | `Pages/Contacts/Show.tsx` | Visual reference — implement in Step 1 / Task 3 |
| `ui-prototypes/projects.html` | `Pages/Projects/Index.tsx` | Visual reference — implement in Step 3 / Task 2 |
| *(no prototype)* | `Pages/Reservations/Show.tsx` | No prototype — use `00-ui-design-system.md §7` milestone spec |
| *(no prototype)* | `Pages/Tasks/Index.tsx` | No prototype — use `00-ui-design-system.md §7` |
| *(no prototype)* | `Pages/Reports/Index.tsx` | No prototype — use `00-ui-design-system.md §7` |

Chief uses prototype screenshots as visual QA targets. The Playwright checks in `00-visual-qa-protocol.md` navigate the actual built app (localhost:8000), not the prototype files.
