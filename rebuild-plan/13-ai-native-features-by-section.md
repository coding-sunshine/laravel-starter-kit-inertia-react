# AI-Native Features by Section (Expanded Suggestions)

This document adds **concrete AI-native feature ideas** per domain so nothing is missed. Use with the starter kit’s **Prism** (`docs/developer/backend/prism.md`) and **Laravel AI SDK** (`docs/developer/backend/ai-sdk.md`), plus **pgvector** and **laravel-ai-memory** where noted.

**Thesys C1 (`THESYS_API_KEY`):** For any feature that renders an AI response to the user, use Thesys C1 generative UI instead of plain text. See **00-thesys-conversational-ai.md** for full integration spec. C1 renders interactive charts, cards, tables, and forms from agent/Prism responses. DataTable `HasAi` uses C1 automatically for NLQ + Visualize on every table.

---

## Step 1 — Contacts & roles

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Contact type suggestion** | During/after import, suggest type (lead/client/agent/partner) from name + company. | Prism or Laravel AI; optional confidence threshold. |
| **Duplicate contact detection** | Before or after import, use embeddings or fuzzy match to flag possible duplicates (same person, two contacts). | Laravel AI embeddings + pgvector similarity, or Prism structured output. |
| **Contact enrichment** | Optional: from company_name or email domain, suggest company details or industry. | Prism one-off call or background job. |
| **Smart stage suggestion** | Suggest next stage (e.g. qualified → proposal) from last activity or notes. | Can defer to Step 5 when notes exist. |

---

## Step 2 — Users & contact link

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Best contact for user** | When a user has no contact_id or multiple candidates, suggest best match from legacy lead data. | Prism or Laravel AI; optional. |

---

## Step 3 — Projects & lots

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Project description summary** | Short summary or meta description from long description (on save or background). | Prism or Laravel AI. |
| **Stage suggestion** | Suggest project stage from description text. | Prism or Laravel AI. |
| **Similar projects** | “Find similar” using embedding of title + description; pgvector similarity. | Laravel AI embeddings, pgvector, HasNeighbors. |
| **Auto-tagging** | Suggest tags (e.g. “SMSF”, “FIRB”) from project description. | Prism or Laravel AI structured output. |
| **Lot recommendation** | For a contact (e.g. client), suggest lots that match preferences (price range, location) — can use RAG or rules. | Laravel AI agent with read-only tools (Step 6). |

---

## Step 4 — Reservations, sales, commissions

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Sale/commission summary** | One-line summary or human-readable commission breakdown from notes and amounts. | Prism or Laravel AI; show on Sale show/Filament. |
| **Next-best-action** | Suggest next step (e.g. “Send contract”, “Follow up finance”) from sale stage and dates. | Prism or small Laravel AI call. |
| **Deal risk / pipeline insight** | Optional: “Deals at risk” (e.g. no activity in 14 days) or simple pipeline summary. | Can be rule-based first; AI to explain “why at risk” later. |
| **Email draft for client** | From sale + contact context, draft a short email (e.g. finance reminder). | Prism; Filament or Inertia action. |

---

## Step 5 — Tasks, relationships, marketing

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Task suggestions** | Suggest next tasks for a contact from stage and history (“Follow up”, “Send proposal”). | Laravel AI or Prism; contact show or task index. |
| **Mail list segmentation** | Suggest segments or tags for contacts (e.g. “High value”, “Needs follow-up”). | Prism or Laravel AI; mail list UI. |
| **Note summarization** | Summarize long notes for a contact (e.g. last 5 notes → one paragraph). | Prism or Laravel AI; contact timeline or note list. |
| **Email draft from contact** | Draft outreach email from contact name, company, and last note. | Prism; button on contact show. |
| **Relationship insight** | Suggest “likely relationship type” between two contacts from notes or activity. | Optional; Prism or Laravel AI. |

---

## Step 6 — AI-native features (dedicated)

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Contact assistant agent** | Laravel AI agent: answers questions about contacts, stages, next steps; tools: search contacts, list recent. | `app/Ai/Agents/`, RemembersConversations, optional WithMemory. |
| **Property/Sales agent** | Agent with read-only tools: query projects, lots, reservations, sales. Optional RAG over descriptions. | Laravel AI SDK, pgvector for RAG. |
| **Prompt commands / Bot-in-a-box** | Migrate ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes; run via Prism or Laravel AI. | Prism or Laravel AI; Filament/Inertia UI. |
| **RAG over contacts/projects** | Embed contact notes, project descriptions; agent uses SimilaritySearch tool to answer “contacts interested in SMSF” etc. | Laravel AI SDK, pgvector, embedding_documents or contact_embeddings. |
| **Ad-hoc drafts** | Email draft, sale summary, next-step text from Prism (e.g. from Filament action or Inertia modal). | Prism `ai()` helper. |
| **Agent memory** | Store/recall user or contact preferences across conversations (e.g. “prefers email over phone”). | laravel-ai-memory, WithMemory middleware. |

---

## Step 7 — Reporting & dashboards

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Dashboard insight** | One or two sentence insight or recommendation (e.g. “3 high-value leads need follow-up”). | Prism or Laravel AI; dashboard controller; cache 5–15 min. |
| **Natural language report query** | Optional: “Show sales last month by agent” → agent or Prism generates query or summary. | Laravel AI agent with read-only report tools; or Prism structured output. |
| **Anomaly hint** | Optional: “Unusual: 2x reservations this week vs last month.” | Rule-based or simple AI; display on dashboard. |

---

## Cross-cutting

| Feature | Description | Kit reference |
|--------|-------------|----------------|
| **Global search with AI** | Search contacts, projects, lots; optional “semantic” search via embeddings. | Laravel AI embeddings + pgvector, or Scout. |
| **Activity log summary** | “What happened with this contact last week?” — summarize activity log entries. | Prism or Laravel AI. |
| **Bulk action suggestions** | “Suggest contacts to add to this mail list” from list rules or AI. | Prism or Laravel AI; optional. |

---

---

## Real Estate–Specific AI Features (2025–2026 standard)

These are becoming baseline expectations in modern property CRMs. Implement in the steps noted.

| Feature | Description | Step | Thesys C1 |
|---------|-------------|------|-----------|
| **Lead score badge** | AI-computed 0–100 score per contact from activity recency, email opens, property views, price-range match. Show on contact list rows + record header. | Step 1 (rule-based) → Step 6 (ML) | Score badge component |
| **Days-Since-Contact alert** | Color-coded (green/amber/orange/red) column in contact list + badge on record. Triggers “stale” smart list. | Step 1 | N/A (computed column) |
| **Smart List auto-suggestions** | AI suggests saved filter sets from usage patterns: “You often filter by source=Zillow + stage=New — save as Smart List?” | Step 6 | C1 suggestion card |
| **Virtual ISA / lead engagement** | AI agent sends initial text/email to new leads within minutes; qualifies budget, timeline, property type; hands off to agent when intent is high. | Step 15 (Phase 2) | C1 conversation UI |
| **Predictive “likely to transact”** | Flag contacts from dormant database predicted to transact in next 90 days (from activity patterns). | Step 15 (Phase 2) | C1 insight card on dashboard |
| **Buyer–Lot matching** | For a buyer contact, suggest matching lots based on price range, suburb, property type from saved search prefs. | Step 6 | C1 property card list |
| **Property–Buyer reverse match** | When a new lot is imported, auto-suggest buyer contacts from DB who match criteria. | Step 6 | C1 contact match cards |
| **Conversation intelligence** | After a call is logged, AI transcribes notes (voice-to-text) and extracts: sentiment, next steps, objections. | Step 6 | C1 structured call summary |
| **Transaction milestone AI** | When sale moves to Under Contract, AI pre-populates the milestone checklist (inspection date, finance date, settlement) from sale data and sends reminders. | Step 4 / Step 6 | C1 checklist card |
| **Commission forecast** | From pipeline (reserved lots + stage probabilities), forecast commission income for next 30/60/90 days. | Step 7 | C1 chart + table |
| **Re-engagement sequences** | For “Past Clients — 12+ months” smart list, AI drafts personalized re-engagement emails referencing their purchase anniversary or market update. | Step 5 (template) → Step 15 (AI-driven) | C1 email draft card |
| **NLQ report builder** | “Show me lead source ROI last quarter” → C1 renders chart + summary on Reports page. | Step 7 | C1 chart component |
| **AI content for project listings** | From project description + features, generate SEO-optimized listing copy, short description, key selling points. | Step 3 | Prism action (no C1 needed) |

---

## Thesys C1 generative UI — by feature type

See **00-thesys-conversational-ai.md** for full spec. Quick reference for what C1 renders per feature type:

| AI Output Type | C1 Component | Use |
|---|---|---|
| Contact list result | `ContactCard` list | Agent returns contacts matching a query |
| Property recommendation | `PropertyCard` grid | Lot matching, buyer suggestions |
| Chart/analysis | `PipelineFunnel` or auto-chart | Pipeline, commission trend, lead source |
| Email draft | `EmailCompose` card | Draft with subject + body + Send action |
| Checklist | `TaskChecklist` | Transaction milestones, next steps |
| Commission table | `CommissionTable` | Sale financial breakdown |
| Insight summary | Generic C1 card | Dashboard insight, call summary |

---

## Implementation priority (suggestion)

- **Must-have for “AI-native”:** Contact assistant agent with C1 UI (Step 6), dashboard insight via C1 (Step 7), lead score badge (Step 1→6), Days-Since-Contact (Step 1), sale/commission summary via C1 (Step 4), DataTable HasAi on all tables (Steps 1, 3, 4).
- **High value:** Buyer–Lot matching via C1, email drafts via C1, note summarization, similar projects RAG, NLQ report builder via C1, transaction milestone AI.
- **Nice-to-have (Phase 2):** Virtual ISA, predictive “likely to transact”, conversation intelligence, re-engagement AI sequences, commission forecast.

Use this list when implementing each step so AI-native features are considered per section; the step files and **09-coverage-audit.md** §3 remain the source of what is in scope for each step.

**Always check 00-thesys-conversational-ai.md** before implementing any AI response that is shown to the user — C1 should render structured UI, not plain text.

---

## DataTable HasAi — Configuration Per Table

Every CRM DataTable must include the `HasAi` trait and define `tableAiSystemContext()`. The context string is the single most important AI configuration — it determines how the NLQ handler interprets column names and builds queries.

### ContactDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a CRM contacts table for a real estate agency. Contacts represent buyers,
investors, and vendors. Key fields: stage (new/qualified/hot/warm/cold/dead), lead_score (0–100,
AI-generated), last_contacted_at (days since last interaction), assigned_agent.
Help agents identify who to follow up with and surface pipeline insights.';
}
```

**AI handlers to enable**: NLQ, insights, enrich (email → job_title/company), suggest (smart list refinements), visualize (stage distribution chart).

**`tableAiQuickPrompts()`**:
```php
return [
    'Who needs follow-up today (stale > 30 days)?',
    'Show hot leads (score > 70) assigned to me',
    'How many contacts were created this week by stage?',
    'Which contacts have no assigned agent?',
];
```

### ProjectDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a real estate project inventory for a property sales agency. Projects are
residential developments (apartments, houses, land). Key fields: stage (pre_launch/selling/completed),
available/reserved/sold lot counts, price range, developer, suburb/state.
Help agents identify projects to present to specific buyer profiles.';
}
```

**AI handlers**: NLQ, insights, suggest (filter refinements), visualize (stage distribution, lot availability chart).

**`tableAiQuickPrompts()`**:
```php
return [
    'Show all selling projects in QLD under $600k',
    'Which projects have the most available lots?',
    'Find SMSF-eligible projects with available lots',
];
```

### LotDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a lot/unit inventory for a property development project. Each lot has
bed/bath/car counts, size in m², price, and a status (available/reserved/sold).
Help agents identify lots that match specific buyer criteria (budget, size, bedrooms).';
}
```

**AI handlers**: NLQ, insights, enrich (price benchmarking suggestion), visualize (status breakdown, price distribution).

**`tableAiQuickPrompts()`**:
```php
return [
    'Find 2-bed lots under $450k that are available',
    'Show lots suitable for SMSF (under $500k, positive yield)',
    'Which lots have been available longest?',
];
```

### ReservationDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a property reservation pipeline for a real estate sales agency. Reservations
track buyers from initial enquiry to settlement. Stages: enquiry/qualified/reservation/contract/
unconditional/settled. Key fields: deposit_status (eWAY payment), settlement_date, assigned_agent.
Help identify at-risk deals, upcoming settlements, and unpaid deposits.';
}
```

**AI handlers**: NLQ, insights (stalled deals, unpaid deposits), visualize (pipeline funnel by stage).

**`tableAiQuickPrompts()`**:
```php
return [
    'Which reservations have unpaid deposits?',
    'Show deals settling in the next 30 days',
    'Which deals have had no stage change in 14 days?',
];
```

### SaleDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a property sales register for a real estate agency. Sales track completed or
in-progress property transactions. Key fields: sale_price, commission_total (sum of all agent commissions),
status (state machine), settled_at. Help calculate commission forecasts and identify pipeline value.';
}
```

**AI handlers**: NLQ, insights (commission anomalies, pending finance), visualize (monthly settlement bar chart, agent commission ranking).

**`tableAiQuickPrompts()`**:
```php
return [
    'Show total commission by agent this quarter',
    'Which sales have finance conditions due this week?',
    'Compare this month\'s settlements vs last month',
];
```

### TaskDataTable

```php
public function tableAiSystemContext(): string
{
    return 'You are analyzing a CRM task list for real estate agents. Tasks are follow-up actions linked to
contacts. Types: call, email, meeting, follow-up. Priority: low/medium/high/urgent.
Help identify overdue tasks, agents with heavy workloads, and contacts without recent follow-up activity.';
}
```

**AI handlers**: NLQ, insights (overdue patterns, workload imbalance), suggest (recommended due dates based on contact stage).

**`tableAiQuickPrompts()`**:
```php
return [
    'Show all overdue high-priority tasks',
    'Which agents have the most tasks due today?',
    'Show contacts with no tasks in the last 30 days',
];
```

### ReportDataTable (dynamic)

Context is set at runtime per report type. Always include what the report measures, key aggregation fields, and what insights are most useful for management. See Step 7 for examples.

**AI handlers**: All 6 enabled (NLQ, insights, enrich, suggest, column-summary, visualize). This is the primary power-user AI entry point for management reporting.
