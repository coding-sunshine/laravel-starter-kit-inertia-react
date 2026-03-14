# Thesys C1 — Conversational AI & Generative UI

This document specifies how to use the **Thesys C1 API** across the Fusion CRM rebuild. Chief must read this alongside `13-ai-native-features-by-section.md` when implementing any AI feature. The `THESYS_API_KEY` is confirmed available.

---

## 1. What Thesys C1 Is

Thesys C1 is an **OpenAI-compatible API middleware** that augments LLMs to respond with **interactive UI components instead of plain text**. Instead of an agent saying "Here are 3 contacts matching your query", it renders a live, interactive contact card list directly in the chat or page. It generates: charts, cards, tables, forms, lists, slides, and reports — all reactive to the conversation context.

**Key facts for implementation:**

| Property | Value |
|---|---|
| API style | OpenAI-compatible (drop-in replacement for `chat/completions`) |
| Frontend SDK | `@thesys/client` React SDK |
| Backend | Works with Laravel AI SDK agents, Prism, or any LLM |
| Tool calls | Full support — connect to CRM data (contacts, projects, sales) via tools |
| Custom components | Custom React components with CRM-specific logic can be registered |
| DataTable HasAi | Already wired via `THESYS_API_KEY` in kit — gives NLQ + Visualize tab on every DataTable |
| Auth | `THESYS_API_KEY` in `.env` |

**Console:** `console.thesys.dev`

---

## 2. Where C1 Applies in Fusion CRM

### 2.1 DataTable HasAi (already in kit — activate in Step 0)

Every CRM DataTable using `HasAi` (Contact, Project, Lot, Sale, Task, Reservation) gets:
- **NLQ search bar** — natural language to filter: "Show contacts from Sydney not contacted in 30 days"
- **Column insights** — C1 explains patterns in the visible data
- **Visualize tab** — C1 generates an interactive chart from table data on demand (requires `THESYS_API_KEY`)

This is configured via `config/data-table.php` → `DATA_TABLE_AI_MODEL` and `THESYS_API_KEY`. No additional code per DataTable — just add `HasAi` to the DataTable class.

### 2.2 Contact Assistant Chat (Step 6)

The existing kit chat UI (`docs/developer/frontend/ai-chat.md`) + Thesys React SDK renders **generative UI responses** instead of text walls:

| Query | C1 renders |
|---|---|
| "Show me hot leads from last week" | Interactive contact card list with stage dots, last-contact date, quick-action buttons |
| "What's the pipeline for Project Aria?" | Pipeline funnel chart with lot count by status (reserved/available/sold) |
| "Draft a follow-up email for John Smith" | Formatted email card with subject + body pre-filled + "Send" action button |
| "What commissions are due this month?" | Interactive table of sales + amounts + Xero status |
| "Which contacts haven't been touched in 60 days?" | Contact list with Days-Since-Contact badge, bulk-reassign action |

**Implementation pattern (Step 6):**
```tsx
// Frontend: wrap agent chat in C1 renderer
import { C1, useC1 } from '@thesys/client';

// Backend: ContactAssistantAgent returns C1-compatible JSON via tool calls
// Tool calls connect to: contacts_search, projects_search, sales_by_contact (MCP tools)
```

### 2.3 Property / Lot Recommendation Engine (Step 6)

When a user asks "Which lots match this buyer's budget?", C1 renders:
- Property card grid (photo, price, status dot, suburb, key stats)
- Inline "Reserve" action button per card
- Filter chips to narrow (price range, suburb, project)

Wire: Laravel AI PropertyAgent → MCP `lots_filter` tool → C1 renders card grid.

### 2.4 Dashboard AI Insight Card (Step 7)

The dashboard bento grid includes an **AI insight card** (§7 of `00-ui-design-system.md`). Use C1 for this:
- Natural language query: "What should I focus on today?"
- C1 renders: priority contact list + 1 chart (e.g. pipeline at risk) + 1 action button (e.g. "Start follow-up sequence")
- Cache the C1 response 5–15 minutes; refresh on demand.

### 2.5 Natural Language Report Query (Step 7)

On the Reports page, add a C1-powered NLQ bar:
- "Show me sales by agent last quarter" → C1 renders a grouped bar chart
- "Which projects have the most reservations?" → C1 renders a ranked table
- "Commission forecast for next 90 days" → C1 renders a time-series projection card

Use Prism + Relay (MCP tools) as the backend; C1 renders the result.

### 2.6 Inline AI Actions in Record Detail Pages (Steps 4, 5)

On Contact detail, Sale detail, and Reservation detail pages:
- "AI Summary" button → C1 renders a structured summary card (not a text blob)
- "Next Steps" button → C1 renders a checklist card with recommended actions + assign buttons
- "Draft Email" button → C1 renders a formatted email composition card

### 2.7 Prompt Commands / Bot-in-a-Box (Step 6)

The 481 legacy `ai_bot_prompt_commands` execute via Prism. Responses are rendered via C1:
- Instead of raw text output in a modal, C1 formats the response as the appropriate UI component (table, card, list, slide)
- The `ai_bot_boxes` UI uses C1 to render bot responses inline

---

## 3. Implementation Map (by step)

| Step | C1 Feature | Priority |
|---|---|---|
| **Step 0** | `THESYS_API_KEY` in `.env`; `DATA_TABLE_AI_MODEL` set; verify DataTable HasAi works | Required |
| **Step 1** | HasAi on ContactDataTable → NLQ + Visualize active | Required |
| **Step 3** | HasAi on ProjectDataTable + LotDataTable → NLQ + Visualize active | Required |
| **Step 4** | HasAi on SaleDataTable → NLQ + Visualize; inline AI summary on Sale detail | High |
| **Step 6** | C1 in ContactAssistant chat responses; Property recommendation cards; prompt command responses | Required |
| **Step 7** | Dashboard AI insight card via C1; NLQ report builder via C1 | High |
| **Step 15** | AI lead nurture insights rendered as C1 timeline cards | Medium |
| **Step 18** | CRM analytics NLQ → C1 chart rendering | High |

---

## 4. Frontend integration (React / Inertia)

In `resources/js/`, install and configure the Thesys React SDK:

```bash
bun add @thesys/client
```

Register custom CRM components so C1 can render them:

```tsx
// resources/js/ai/thesys-components.tsx
import { registerComponent } from '@thesys/client';

registerComponent('ContactCard', ContactCard);        // contact summary with stage dot + actions
registerComponent('PropertyCard', PropertyCard);      // lot/project card with photo + price
registerComponent('PipelineFunnel', PipelineFunnel);  // funnel chart from stage counts
registerComponent('EmailCompose', EmailCompose);      // email card with subject/body/send
registerComponent('CommissionTable', CommissionTable); // sale commission breakdown
registerComponent('TaskChecklist', TaskChecklist);    // task card with check/assign actions
```

C1 will automatically select the right component based on tool call results. Use `@thesys/client`'s `<C1 />` wrapper to render the streaming response.

---

## 4.5 Custom Component Field Specifications

Chief must implement these TypeScript interfaces exactly. C1 selects the correct component based on the `component` key returned in the agent tool response.

### ContactCard

```tsx
interface ContactCardProps {
  id: number;
  full_name: string;
  email?: string;
  phone?: string;
  suburb?: string;
  state?: string;
  stage: string;               // e.g. "Hot", "Nurture", "Property Reserved"
  lead_score: number;          // 0–100; badge color: <30 red, 30–60 amber, >60 green
  last_contacted_at?: string;  // ISO 8601; color: <7d green, 7–30d amber, >30d red
  assigned_agent?: {
    id: number;
    name: string;
    avatar_url?: string;
  };
  tags?: string[];
  actions?: Array<{
    label: string;             // e.g. "Call", "Send Email", "View Record", "Create Task"
    type: 'link' | 'action';
    href?: string;             // for type=link (Inertia route)
    action?: string;           // for type=action: "create_task" | "send_email" | "log_call"
    payload?: Record<string, unknown>;
  }>;
}
```

**MCP tool that feeds this**: `contacts_search` → returns array → C1 renders one `ContactCard` per result.

**Example agent tool response** (what the `contacts_search` MCP tool returns):

```json
{
  "component": "ContactCard",
  "props": {
    "id": 42,
    "full_name": "Sarah Chen",
    "email": "sarah@example.com",
    "phone": "0412 345 678",
    "suburb": "Surry Hills",
    "stage": "Hot",
    "lead_score": 87,
    "last_contacted_at": "2026-03-11T09:30:00Z",
    "assigned_agent": { "id": 3, "name": "James Wong" },
    "tags": ["investor", "2br"],
    "actions": [
      { "label": "View Record", "type": "link", "href": "/contacts/42" },
      { "label": "Send Email", "type": "action", "action": "send_email", "payload": { "contact_id": 42 } },
      { "label": "Create Task", "type": "action", "action": "create_task", "payload": { "contact_id": 42 } }
    ]
  }
}
```

---

### PropertyCard

```tsx
interface PropertyCardProps {
  id: number;
  type: 'project' | 'lot';
  title: string;
  suburb?: string;
  state?: string;
  stage?: string;                 // for projects: "Pre-Launch", "Selling", "Completed"
  title_status?: 'available' | 'reserved' | 'sold'; // for lots
  photo_url?: string;             // hero image; fallback: gradient tile with initials
  min_price?: number;             // for projects
  price?: number;                 // for lots (exact price)
  bedrooms?: number;
  bathrooms?: number;
  car?: number;
  internal_m2?: number;
  total_m2?: number;
  project_title?: string;         // for lots — parent project name
  is_hot_property?: boolean;
  available_lots_count?: number;  // for projects
  actions?: Array<{
    label: string;                // e.g. "View Lots", "Reserve", "View Project"
    type: 'link' | 'action';
    href?: string;
    action?: string;              // "open_lot_sheet" | "start_reservation"
    payload?: Record<string, unknown>;
  }>;
}
```

**MCP tools that feed this**: `lots_filter`, `projects_search` → C1 renders a card grid.

---

### PipelineFunnel

```tsx
interface PipelineFunnelProps {
  title: string;               // e.g. "Active Reservation Pipeline"
  stages: Array<{
    name: string;              // stage label
    count: number;             // number of items at this stage
    value?: number;            // total dollar value at this stage (optional)
    color?: string;            // hex/oklch override; default: brand sequential palette
  }>;
  total_count: number;
  total_value?: number;
  currency?: string;           // default "AUD"
}
```

**MCP tool**: `pipeline_summary` → aggregates reservations/sales by stage → feeds funnel.

---

### EmailCompose

```tsx
interface EmailComposeProps {
  to: { name: string; email: string };
  from?: { name: string; email: string };
  subject: string;
  body: string;                 // pre-filled Markdown or HTML; rendered in preview pane
  contact_id?: number;          // if linked to a contact record
  thread_id?: string;           // existing mail thread to reply in
  actions: Array<{
    label: string;              // "Send Now", "Edit Draft", "Discard"
    type: 'submit' | 'edit' | 'dismiss';
  }>;
}
```

**Backend plumbing**: `send_email` action → `backstage/laravel-mails` `Mailable` → logged automatically.

---

### CommissionTable

```tsx
interface CommissionTableRow {
  commission_type: 'piab' | 'subscriber' | 'affiliate' | 'sales_agent'
                 | 'referral_partner' | 'bdm' | 'sub_agent';
  agent_name?: string;          // null for platform types (piab)
  rate_percentage?: number;     // e.g. 2.5 (percent)
  amount: number;               // dollar amount
  override_amount: boolean;     // true = manually set, not formula-derived
}

interface CommissionTableProps {
  sale_id: number;
  lot_title: string;
  project_title: string;
  sale_price: number;
  rows: CommissionTableRow[];
  total_commission: number;
  currency?: string;            // default "AUD"
}
```

**MCP tool**: `commissions_by_sale` → returns rows → C1 renders sortable table with totals row.

---

### TaskChecklist

```tsx
interface TaskChecklistItem {
  id: number;
  title: string;
  due_at?: string;              // ISO 8601; overdue if past now
  priority: 'low' | 'medium' | 'high' | 'urgent';
  type: 'call' | 'email' | 'meeting' | 'follow_up' | 'other';
  is_completed: boolean;
  assigned_to?: { id: number; name: string; avatar_url?: string };
  contact?: { id: number; name: string; href: string };
  actions: Array<{
    label: string;              // "Mark Done", "Assign to Me", "Snooze 1 Day"
    type: 'complete' | 'assign' | 'snooze' | 'link';
    href?: string;
  }>;
}

interface TaskChecklistProps {
  title: string;                // e.g. "Your Tasks for Today", "Overdue Tasks"
  tasks: TaskChecklistItem[];
  show_contact?: boolean;       // true = show linked contact name per row
  actions?: Array<{             // list-level actions
    label: string;              // "Create Task", "View All Tasks"
    type: 'link' | 'action';
    href?: string;
  }>;
}
```

**MCP tool**: `tasks_for_user` → returns today's/overdue tasks → C1 renders interactive checklist.

---

## 4.6 Agent → C1 Full Response Flow

This is the **complete data flow** Chief must implement for every AI agent interaction that produces UI output:

```
User message (chat panel)
  ↓
1. POST /api/chat (Inertia/fetch — streaming)
  ↓
2. ContactAssistantAgent (Laravel AI SDK)
   - receives user message + injected memory (Cohere-reranked)
   - selects MCP tool call (e.g. contacts_search)
  ↓
3. MCP Tool executes (app/Mcp/Tools/ContactSearchTool.php)
   - queries PostgreSQL contacts table
   - returns JSON array of contact data
  ↓
4. Agent packages tool result as C1 component JSON:
   { "component": "ContactCard", "props": { ...contact fields... } }
   for each result item
  ↓
5. Agent response streams via NDJSON (AG-UI protocol)
  ↓
6. Frontend <C1 /> component receives stream
   - @thesys/client SDK deserializes NDJSON
   - renders registered ContactCard React component with props
   - NOT a plain <p> text string
  ↓
7. User sees: interactive contact cards with action buttons
   - "View Record" → Inertia link to /contacts/{id}
   - "Send Email" → opens EmailCompose card inline
   - "Create Task" → opens TaskChecklist create flow
```

**Backend tool return shape** (what each MCP tool must return for C1 to pick up):

```php
// app/Mcp/Tools/ContactSearchTool.php
public function handle(array $input): array
{
    $contacts = Contact::search($input['query'])
        ->where('contact_origin', 'property')
        ->take(5)
        ->get();

    return [
        'component' => 'ContactCard',  // ← C1 uses this to pick the React component
        'multiple' => true,            // ← renders a card for each item
        'items' => $contacts->map(fn($c) => [
            'id'                => $c->id,
            'full_name'         => $c->full_name,
            'email'             => $c->email,
            'phone'             => $c->phone,
            'suburb'            => $c->suburb,
            'stage'             => $c->stage,
            'lead_score'        => $c->lead_score,
            'last_contacted_at' => $c->last_contacted_at?->toIso8601String(),
            'assigned_agent'    => $c->assignedUser ? [
                'id'   => $c->assignedUser->id,
                'name' => $c->assignedUser->name,
            ] : null,
            'tags'    => $c->tags ?? [],
            'actions' => [
                ['label' => 'View Record', 'type' => 'link', 'href' => "/contacts/{$c->id}"],
                ['label' => 'Send Email',  'type' => 'action', 'action' => 'send_email', 'payload' => ['contact_id' => $c->id]],
                ['label' => 'Create Task', 'type' => 'action', 'action' => 'create_task', 'payload' => ['contact_id' => $c->id]],
            ],
        ])->toArray(),
    ];
}
```

**`WithMemoryUnlessUnavailable` middleware registration** — add to every CRM agent class:

```php
// app/Ai/Agents/ContactAssistantAgent.php
use App\Ai\Middleware\WithMemoryUnlessUnavailable;

protected function middleware(): array
{
    return [
        new WithMemoryUnlessUnavailable(
            userId: auth()->id(),
            threshold: 0.5,          // cosine similarity minimum
            topK: 5,                 // max recalled memories
            rerankModel: 'rerank-english-v3.0',  // Cohere reranker
        ),
    ];
}
```

Apply `WithMemoryUnlessUnavailable` to: ContactAssistantAgent, PropertyAgent, DashboardInsightAgent, BotInABoxAgent.

---

## 5. Backend wiring (Laravel)

C1 is OpenAI-compatible. Use it as the provider in Prism or Laravel AI SDK for any feature that needs generative UI output:

```php
// Option A: Prism with Thesys endpoint
ai()->using('thesys')->prompt($prompt)->generate();

// Option B: Laravel AI SDK agent — set THESYS_API_KEY, configure provider in config/ai.php
// The agent tool calls hit CRM MCP tools (contacts_search, lots_filter, etc.)
// C1 wraps the response in appropriate UI component
```

For DataTable HasAi: no backend changes — the kit's DataTable package handles this automatically via `DATA_TABLE_AI_MODEL` + `THESYS_API_KEY`.

---

## 6. Env vars checklist

Add to `.env` in Step 0:

```env
# Thesys C1 — generative UI
THESYS_API_KEY=your-thesys-api-key

# DataTable AI (NLQ + Visualize on every HasAi DataTable)
DATA_TABLE_AI_MODEL=gpt-4o-mini   # or preferred model

# Main AI providers (for Laravel AI SDK agents + Prism)
OPENAI_API_KEY=...                 # or
OPENROUTER_API_KEY=...             # OpenRouter for Prism (multi-model)
```

---

## 7. What NOT to do

- Do NOT render C1 responses as plain `<p>` text strings — the whole point is generative UI. Always use the `<C1 />` component wrapper.
- Do NOT use C1 for every micro-interaction (e.g. form validation). C1 is for AI-driven responses that benefit from structured visual output.
- Do NOT skip the custom component registration — without it, C1 falls back to generic components that look generic.
- Do NOT hardcode Thesys endpoint URLs. Use the `@thesys/client` SDK which handles routing.

---

## 8. References

- Thesys console: `console.thesys.dev`
- Thesys docs: `www.thesys.dev`
- Kit DataTable HasAi: `config/data-table.php`, `docs/developer/backend/data-table.md`
- Kit AI agents: `docs/developer/backend/ai-sdk.md`, `app/Ai/Agents/`
- Kit Prism: `docs/developer/backend/prism.md`
- Kit MCP tools: `app/Mcp/Servers/ApiServer.php` (Step 6 adds CRM tools)
