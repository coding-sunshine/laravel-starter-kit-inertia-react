# Step 6: AI-Native Features

## Goal

Make the new CRM **AI-native** using the starter kit’s Prism (OpenRouter), Laravel AI SDK (agents, embeddings, RAG), and optional pgvector. Integrate AI for contact/lead assistance, property search, sales insights, and CRM bot (replacing/improving legacy “Bot in a Box”).

## Suggested implementation order

1. **Migrations**: Create ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes (org-scoped); optional contact_embeddings or embedding_documents for RAG.
2. **Import**: Implement `fusion:import-ai-bot-config`; run and verify.
3. **Contact assistant**: Create ContactAssistantAgent (Laravel AI), wire tools (search contacts, list recent), expose via kit chat UI or Inertia page.
4. **Optional**: RAG (embeddings table + SimilaritySearch tool), Property/Sales agent, Prism ad-hoc actions (email draft, sale summary).

## Starter Kit References

- **Prism**: `docs/developer/backend/prism.md` — `ai()` helper, OpenRouter, structured output, MCP/Relay
- **Laravel AI SDK**: `docs/developer/backend/ai-sdk.md` — agents, embeddings, RAG, provider tools
- **pgvector**: `docs/developer/backend/pgvector.md` — vector column, HasNeighbors, similarity search
- **AI Memory**: `docs/developer/backend/ai-memory.md` — AgentMemory, WithMemory middleware, StoreMemory/RecallMemory tools. **Use WithMemoryUnlessUnavailable** (kit’s `App\Ai\Middleware\WithMemoryUnlessUnavailable`) for CRM agents so memory recall works when embeddings are unavailable (e.g. OpenRouter without OpenAI embeddings); do not use the standard WithMemory only. See 00-kit-package-alignment.md.
- **Agent conversations**: Kit already has `agent_conversations` and `agent_conversation_messages` (Laravel AI `RemembersConversations`)
- **Frontend**: `docs/developer/frontend/ai-chat.md` — chat UI for agents
- **Thesys C1**: `rebuild-plan/00-thesys-conversational-ai.md` — render all AI responses as generative UI (cards, charts, tables) via `@thesys/client` React SDK + `THESYS_API_KEY`. Do NOT render agent responses as plain text strings.

## Deliverables

1. **CRM AI agents**
   - **Contact/Lead agent**: Laravel AI agent (e.g. `app/Ai/Agents/ContactAssistantAgent`) with instructions to answer questions about contacts, lead stage, next steps. Use `RemembersConversations` and **WithMemoryUnlessUnavailable** middleware (not standard WithMemory) so memory recall works when embeddings are unavailable. Tools: e.g. search contacts (via DTO or DB), list recent contacts. Expose via existing chat UI or new Inertia page.
   - **Property/Sales agent** (optional): Agent that can query projects, lots, reservations, sales (read-only tools). Use **WithMemoryUnlessUnavailable** for this agent too. Use RAG over project/lot descriptions if desired (pgvector embeddings).

2. **RAG and embeddings (optional)**
   - **Embedding storage**: Table(s) for embeddable CRM entities — e.g. `contact_embeddings` (contact_id, embedding vector, content_snapshot) or a generic `embedding_documents` (document_type, document_id, content, embedding). Use Laravel AI `Embeddings::for()->generate()` and store with pgvector (`vector` column). See `pgvector.md` and `embedding_demos` pattern.
   - **RAG tool**: Laravel AI `SimilaritySearch` tool (or custom) that queries embeddings by similarity so the agent can “read” contact notes, project descriptions, or sale summaries. Wire into ContactAssistantAgent or PropertyAgent.

## UI Specification

### Bot-in-a-Box — Floating Widget + Sheet

**Placement**: Floating button (bottom-right corner, 56px circle, brain icon). Click → expands to a side-panel `<Sheet side="right">` (400px wide, full-height). Does NOT use a modal or full page. The rest of the CRM screen remains fully usable while the panel is open.

```
┌────────────────────────────────┐
│ ✕  Fusion AI                  │
├────────────────────────────────┤
│  [message history]             │
│  ← Thesys C1 renders cards,   │
│    tables, property cards,     │
│    commission tables, checklists│
├────────────────────────────────┤
│  [text input]          [Send]  │
│  Suggested prompts (chips):    │
│  "Who needs follow-up today?"  │
│  "Find lots for [contact]"     │
└────────────────────────────────┘
```

**Implementation**: Persistent conversation per user session via TanStack `useChat` hook, `/api/chat` streaming endpoint, AG-UI NDJSON protocol (Laravel AI SDK streaming). Memory injected via `WithMemoryUnlessUnavailable` middleware on every agent run.

**Suggested prompts** (chips below input): Rendered from `tableAiQuickPrompts()` per active page context, refreshed on route change. Examples: "Show leads due for follow-up today", "What reservations are settling this month?", "Find SMSF lots under $550k".

### Reverb Broadcast Events

All events broadcast on **private user channel** `App.Models.User.{id}`. Frontend: `Echo.private('App.Models.User.' + userId).listen(...)` → toast notifications + Inertia partial reloads.

| Event | Trigger | Payload | Frontend Action |
|---|---|---|---|
| `ContactCreated` | Contact saved | `id, name, stage` | Toast + refresh contact count |
| `ContactLeadScoreUpdated` | Score job completes | `contact_id, score, delta` | Inline badge update |
| `ReservationStageChanged` | State transition | `reservation_id, from, to` | Toast + dashboard refresh |
| `TaskDue` | Scheduled 1h before due | `task_id, title, contact_name` | Toast notification |
| `ImportProgress` | Import command chunk | `table, processed, total` | Progress bar on import page |
| `AgentResponse` | Agent streaming complete | `thread_id, summary` | Flash C1 response in chat panel |

### Memory & RAG Configuration

- **Embeddings model**: OpenAI `text-embedding-3-small`, 1536 dimensions
- **Storage**: `memories` table via `AgentMemory` facade (laravel-ai-memory, pgvector `vector` column)
- **Middleware**: `WithMemoryUnlessUnavailable` on ALL CRM agents (gracefully skips if OpenAI key absent)
- **Recall**: Cosine similarity threshold 0.5, top 5 results per middleware call, Cohere `rerank-english-v3.0` on recalled memories before injection into agent context
- **RAG sources**: Contact notes + emails (backstage/laravel-mails logs) indexed as embeddings. Project descriptions indexed. Queried via Laravel AI `SimilaritySearch` tool in ContactAssistantAgent and PropertyAgent.
- **Memory persistence**: Per-user conversation memory. Agent can recall cross-session context: "last time we talked about John Smith, you wanted to follow up on his budget"

3. **Bot In A Box agent infrastructure (Step 5 data → Step 6 AI logic)**

   > **Relationship clarification**: Bot In A Box (Step 5) creates the data model (`ai_bots`, `ai_bot_prompts`, `ai_bot_runs`, `ai_bot_categories`). Step 6 adds the AI execution layer that powers those bots. The bots are **NOT** the same as `ContactAssistantAgent` or `PropertyAgent` — those are autonomous agents with tools and memory. Bots are **template-driven Prism calls** (user picks a template, provides context, clicks Generate).

   **Bot execution flow** (implemented in Step 6):
   - `POST /bot-in-a-box/{bot}/run` → `BotRunController::run()`
   - Loads `AiBot` + selected `AiBotPrompt` template
   - If `realtime_data_injected = true`, calls `CrmContextTool::handle()` to get live CRM summary
   - Calls `ai()->using(provider: 'openrouter', model: $request->model)->send()` (Prism)
   - Streams response via `StreamedResponse` (SSE) to frontend
   - Saves `AiBotRun` record with prompt, output, model used
   - Frontend: `EventSource` → C1 renders streaming output

   **Legacy parity**: Migrate **ai_bot_categories** (11), **ai_bot_prompt_commands** (481), **ai_bot_boxes** (46) from MySQL via `fusion:import-ai-bot-config`. Map `ai_bot_boxes` → `ai_bots`; `ai_bot_prompt_commands` → `ai_bot_prompts`.

4. **Dashboard AI agents** (new in Step 6)

   Two new agents required by the Step 7 dashboard:

   **`DashboardInsightAgent`** (`app/Ai/Agents/DashboardInsightAgent.php`):
   - Runs as a **scheduled job** (`DashboardInsightJob`, daily at 6 AM)
   - System context: CRM overview — contacts by stage, active reservations, overdue tasks, commissions this month
   - Tools: `GetContactSummary`, `GetReservationSummary`, `GetTaskSummary` (all read-only DB queries wrapped as Laravel AI tools)
   - Output: structured JSON with 3–5 insight bullets + action items
   - Result stored in cache: `Cache::put('dashboard_insight_' . $orgId, $result, now()->addHours(24))`
   - Frontend: dashboard reads cache and renders via Thesys C1 as interactive card

   **`DashboardNlqAgent`** (`app/Ai/Agents/DashboardNlqAgent.php`):
   - Triggered on-demand from the dashboard NLQ bar
   - System context: full CRM schema summary (tables, key columns)
   - Tools: `contacts_search`, `projects_search`, `lots_filter`, `reservations_by_contact`, `sales_by_contact` (same MCP tools from deliverable 6)
   - Uses `WithMemoryUnlessUnavailable` middleware
   - Output: structured Thesys C1 response (chart or table or insight card)
   - Endpoint: `POST /api/dashboard/nlq` → streaming via AG-UI NDJSON

4. **Thesys C1 generative UI (required)**
   - Install `@thesys/client` React SDK. Register custom CRM components: **ContactCard**, **PropertyCard**, **PipelineFunnel**, **EmailCompose**, **CommissionTable**, **TaskChecklist**. Wire the ContactAssistantAgent and PropertyAgent to return C1-compatible responses so the chat UI renders interactive components, not plain text. See **00-thesys-conversational-ai.md** §4–5 for full implementation spec.

5. **Real estate AI features (property-focused)**
   - **Lead score**: Compute a 0–100 score per contact from: days since last contact, email open rate, number of property views, price range match, last call outcome. Store as `contacts.lead_score` (nullable int). Refresh via background job or on contact page load. Display as badge on contact list + record header. See `00-ui-design-system.md` §7.
   - **Buyer–Lot matching**: Given a contact (buyer), find matching lots by: price range, suburb, property type, availability (status = available). Expose as tool `lots_match_for_contact(contact_id)` in the PropertyAgent; C1 renders matching property cards.
   - **Property–Buyer reverse match**: When a lot is imported or status changes to available, suggest buyer contacts from DB who match criteria (from their preferences or search history if available). Background job queues suggestions; surfaced on Lot detail page.

6. **CRM MCP tools (laravel/mcp)**
   - Register CRM tools in **App\Mcp\Servers\ApiServer** (or kit equivalent): **contacts_search**, **contacts_show**, **projects_search**, **lots_filter**, **lots_match_for_contact**, **sales_by_contact**, **reservations_by_contact**. So agents and external AI clients can query CRM via MCP. See 00-kit-package-alignment.md.

7. **Prism + Relay (prism-php/relay)**
   - Prism ad-hoc calls can use **prism-php/relay** to call the CRM MCP tools (e.g. contacts_search, projects_search) during generation. See kit `docs/developer/backend/prism.md` Relay section.

8. **Prism for ad-hoc CRM actions (C1 output)**
   - Use Prism (`ai()`) for one-off generation: email draft from contact context, summary of a sale, next-step suggestions, lot description copy. Render output via Thesys C1 (`EmailCompose`, generic card) not raw text. Triggered from Filament actions or Inertia modals.

9. **Import AI config (required for parity)**
   - Command `fusion:import-ai-bot-config`. Source: MySQL **ai_bot_categories**, **ai_bot_prompt_commands**, **ai_bot_boxes**. Target: New PostgreSQL ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes (org-scoped). Map organization_id for multi-tenant.

10. **Subscriber-facing AI chat — org + role scoping (required)**

    Every subscriber (and admin) must be able to chat with the AI and have it answer questions using **only the data they are authorised to see**. This is enforced at the agent tool layer, not just the UI layer.

    ### Tool-level org scoping

    All CRM agent tools that query the database **must** apply org and role filters automatically. Use a shared `WithOrgScope` concern injected into every tool:

    ```php
    // app/Ai/Tools/Concerns/WithOrgScope.php
    trait WithOrgScope
    {
        protected function scopeToOrg(Builder $query): Builder
        {
            $user = auth()->user();

            // Superadmin: no restriction
            if ($user->hasRole('superadmin')) {
                return $query;
            }

            // Admin / subscriber / agent: restrict to their org
            return $query->where('organization_id', $user->organization_id);
        }
    }
    ```

    Apply in every tool:
    ```php
    // app/Ai/Tools/ContactsSearchTool.php
    public function handle(string $query): array
    {
        return Contact::search($query)
            ->tap(fn ($q) => $this->scopeToOrg($q))
            ->limit(10)
            ->get()
            ->toArray();
    }
    ```

    **All tools that must use `WithOrgScope`**: `contacts_search`, `contacts_show`, `lots_filter`, `lots_match_for_contact`, `reservations_by_contact`, `sales_by_contact`, `projects_search`, `GetContactSummary`, `GetReservationSummary`, `GetTaskSummary`.

    ### Role-based capability matrix

    The same floating chat widget appears for all authenticated roles. The **agent instructions** and **available tools** differ by role:

    | Capability | Subscriber | Admin | Superadmin |
    |---|---|---|---|
    | Chat with own org's contacts | ✅ | ✅ | ✅ (all orgs) |
    | Chat with own org's projects/lots | ✅ | ✅ | ✅ (all orgs) |
    | Chat with own org's sales/reservations | ✅ | ✅ | ✅ (all orgs) |
    | Chat with own org's tasks | ✅ | ✅ | ✅ (all orgs) |
    | Ask about other orgs' data | ❌ (scoped out) | ❌ (scoped out) | ✅ |
    | See financial/commission data | ❌ (agent role only) | ✅ | ✅ |
    | Ask about users/permissions | ❌ | ✅ | ✅ |
    | Trigger bulk actions via bot | ❌ | ✅ | ✅ |
    | Ask about platform-wide stats | ❌ | ❌ | ✅ |

    Resolved at runtime via the agent's system prompt:

    ```php
    // app/Ai/Agents/ContactAssistantAgent.php
    protected function systemPrompt(): string
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $orgName = $user->organization?->name ?? 'your organisation';

        return match(true) {
            $user->hasRole('superadmin') => "You are the Fusion CRM AI assistant with full platform access. You can query all organisations and all data.",
            $user->hasRole('admin')      => "You are the Fusion CRM AI assistant for {$orgName}. You can only access data belonging to this organisation.",
            default                      => "You are the Fusion CRM AI assistant for {$orgName}. You can only access contacts, properties, reservations, and tasks belonging to this organisation. You cannot access financial data, user management, or other organisations.",
        };
    }
    ```

    ### Role-aware suggested prompt chips

    The suggested prompts shown below the chat input are rendered from a per-role map (resolved in `tableAiQuickPrompts()` based on `auth()->user()->getRoleNames()->first()` and the current route):

    | Role | Example suggested prompts |
    |---|---|
    | **subscriber** | "Show my leads due for follow-up today", "Find SMSF lots under $600k", "Summarise my pipeline this month", "What reservations are settling soon?" |
    | **admin** | All subscriber prompts + "Show agent performance this quarter", "Which leads haven't been contacted in 14 days?", "Summarise commissions due" |
    | **superadmin** | All admin prompts + "Show all orgs with active reservations", "Which subscribers are on the Starter plan?", "Platform-wide lead volume this month" |

    ### UI visibility

    The floating AI button (56px circle, bottom-right) is visible to **all authenticated roles** including subscribers. No role-gate on the widget itself — scoping is enforced at the tool/data layer.

    ### Agent conversation isolation

    `agent_conversations` rows already include `user_id`. Subscribers can only load their own conversation history:
    ```php
    // enforced in AgentConversationPolicy
    public function view(User $user, AgentConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
    ```
    Superadmins can view any conversation for support/audit purposes.

## DB Design (this step)

- **agent_conversations** / **agent_conversation_messages**: Already in kit.
- **memories**: Already from laravel-ai-memory (pgvector).
- New: **ai_bot_categories**, **ai_bot_prompt_commands**, **ai_bot_boxes** (title, description, page_overview, type, visibility, status, ai_bot_category_id). Optional: **contact_embeddings** or **embedding_documents** for RAG.

## Data Import

- **Source**: MySQL ai_bot_categories, ai_bot_prompt_commands, **ai_bot_boxes**.
- **Target**: New PostgreSQL ai_bot_categories, ai_bot_prompt_commands, ai_bot_boxes. Map organization_id for multi-tenant.

## AI Enhancements (in this step)

- Contact assistant agent with optional memory and RAG.
- Property/Sales agent with read-only tools (and optional RAG).
- Prompt commands / templates managed in app and run via Prism or Laravel AI.
- Optional: Background job to refresh contact/project embeddings for RAG.
- *Full list and priorities (RAG, ad-hoc drafts, agent memory, etc.):* **13-ai-native-features-by-section.md** § Step 6.

## Verification (verifiable results, data fully imported)

- After AI config import, verify: **ai_bot_categories** = 11, **ai_bot_prompt_commands** = 481, **ai_bot_boxes** = 46. Implement `php artisan fusion:verify-import-ai-bot-config`. See **11-verification-per-step.md** § Step 6 and **10-mysql-usage-and-skip-guide.md**.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 7 until the human has completed the checklist below.**

Human must:
- [ ] Confirm AI config import ran and verification PASS (ai_bot_categories = 11, ai_bot_prompt_commands = 481, ai_bot_boxes = 46).
- [ ] Confirm at least one Laravel AI agent (e.g. ContactAssistant) or prompt command is callable (e.g. chat page or Filament).
- [ ] **Subscriber scoping test**: Log in as a subscriber user and confirm the chat bot only returns data from that subscriber's org (ask "show my contacts" — must not show contacts from other orgs).
- [ ] **Cross-role scoping test**: Log in as superadmin and confirm the same chat query returns data across all orgs.
- [ ] Optionally test a prompt command or agent conversation in the UI.
- [ ] Approve proceeding to Step 7 (reporting and dashboards).

## Acceptance Criteria

- [ ] At least one Laravel AI agent (ContactAssistant or equivalent) is wired and callable from the app (chat or page).
- [ ] Optional RAG: embeddings stored and queryable; agent can use similar content in answers.
- [ ] Legacy AI bot config (categories, prompt commands, boxes) migrated; UI to manage them.
- [ ] Verification confirms ai_bot_categories = 11, ai_bot_prompt_commands = 481, ai_bot_boxes = 46.
- [ ] `WithOrgScope` trait applied to all CRM agent tools; subscriber chat cannot retrieve data outside their org.
- [ ] Role-aware system prompt resolves correctly for subscriber, admin, and superadmin roles.
- [ ] Role-aware suggested prompt chips render correctly per role.
- [ ] `AgentConversationPolicy` prevents subscribers from loading other users’ conversation history.
- [ ] Documentation references starter kit’s prism.md, ai-sdk.md, pgvector.md, ai-memory.md.

**UI acceptance criteria (verified by Visual QA protocol — Task 7 checks):**
- [ ] Floating AI button visible in bottom-right on every CRM page (56px circle)
- [ ] Clicking button opens 400px wide Sheet panel from right side (does not navigate away)
- [ ] Chat panel has text input, Send button, and ≥1 suggested prompt chip below input
- [ ] Sending a message receives a response rendered via Thesys C1 (component or card — NOT bare `<p>` tag)
- [ ] lead_score badge on `/contacts/{id}` header (colored: red/amber/green by value)
- [ ] lead_score badge on contact list rows
- [ ] Memory: sending "remember that I prefer email follow-ups" + refreshing and asking "what do I prefer?" returns the preference (cross-session memory test)
- [ ] Subscriber role: floating chat widget visible; asking "show my contacts" returns only that subscriber's org contacts (not other orgs)
- [ ] Superadmin role: same query returns contacts across all orgs
- [ ] Suggested prompt chips differ between subscriber and superadmin views
