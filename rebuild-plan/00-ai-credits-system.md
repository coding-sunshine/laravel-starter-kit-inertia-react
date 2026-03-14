# AI Credits System — Fusion CRM v4

Covers: per-plan AI credit allocation, per-user overrides, BYOK (bring your own key), usage tracking, and superadmin configurability. Chief implements this in Step 0 (schema + config) and Step 23 (plan-level wiring + UI).

---

## 1. Core Concepts

| Concept | Description |
|---|---|
| **AI Credit** | One unit of AI consumption. Deducted per AI interaction. |
| **Plan Credits** | Default credits allocated to an org per billing period. Configurable per plan by superadmin. Default: 100. |
| **User Credit Limit** | Optional per-user cap within an org's total. Unset = no per-user cap (uses org pool). |
| **BYOK** | Bring Your Own Key. User provides their own OpenAI/OpenRouter API key. Bypasses credit counter entirely for that user. |
| **Credit Period** | Credits reset each billing period (monthly). Unused credits do NOT roll over. |
| **Global Default** | Superadmin can set a system-wide default credit amount that applies to new plans. |

---

## 2. Credit Costs per AI Action

| Action | Credits | Notes |
|---|---|---|
| Chat message (send + receive) | 1 | One round-trip = 1 credit |
| DataTable NLQ query | 1 | `handleAiQuery()` call |
| DataTable AI insight | 2 | `handleAiInsights()` |
| DataTable AI suggest | 1 | `handleAiSuggest()` |
| DataTable column summary | 1 | `handleAiColumnSummary()` |
| DataTable AI enrich | 2 | `handleAiEnrich()` — per-row enrichment |
| DataTable AI visualize | 2 | `handleAiVisualize()` |
| Email draft generation | 2 | Draft from contact context |
| Lead score computation | 1 | Per contact score refresh |
| Property match suggestion | 1 | Buyer-lot matching |
| AI summary (contact/sale) | 1 | Summary generation |
| Dashboard AI insight | 2 | KPI insight generation |
| Bulk AI enrich (per 10 rows) | 5 | Bulk enrichment batch |

**Cost config**: All costs above are stored in `config/ai-credits.php` (not hardcoded). Superadmin can adjust via Filament → Settings → AI Credits.

---

## 3. Database Schema

```sql
-- Org-level credit pool
CREATE TABLE ai_credit_pools (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    credits_total   INT NOT NULL DEFAULT 100,   -- set from plan on period reset
    credits_used    INT NOT NULL DEFAULT 0,
    period_start    DATE NOT NULL,              -- start of current billing period
    period_end      DATE NOT NULL,              -- end of current billing period
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (organization_id, period_start)
);

-- Per-user credit limit override (optional)
CREATE TABLE ai_user_credit_limits (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    credits_limit   INT NOT NULL,               -- max this user can use per period (from org pool)
    credits_used    INT NOT NULL DEFAULT 0,     -- this user's consumption this period
    period_start    DATE NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (user_id, period_start)
);

-- Usage log (per interaction)
CREATE TABLE ai_credit_usage (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action          VARCHAR(60) NOT NULL,       -- e.g. 'chat_message', 'nlq_query'
    credits         INT NOT NULL,               -- credits consumed
    model           VARCHAR(100) NULL,          -- model used (e.g. 'openai/gpt-4o-mini')
    byok            BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL
);
CREATE INDEX ON ai_credit_usage (organization_id, created_at DESC);
CREATE INDEX ON ai_credit_usage (user_id, created_at DESC);

-- BYOK per user
CREATE TABLE ai_user_byok (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    provider        VARCHAR(30) NOT NULL,       -- 'openai' | 'openrouter'
    api_key_enc     TEXT NOT NULL,              -- encrypted with app key
    model_override  VARCHAR(100) NULL,          -- e.g. 'gpt-4o', null = use plan default
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

**Plans table addition** (extend kit's plans table via migration):
```sql
ALTER TABLE plans ADD COLUMN ai_credits_per_period INT NOT NULL DEFAULT 100;
```

**System config** (editable via Filament Settings):
```php
// config/ai-credits.php
return [
    'default_credits_per_period' => env('AI_CREDITS_DEFAULT', 100),
    'costs' => [
        'chat_message'        => 1,
        'nlq_query'           => 1,
        'ai_insights'         => 2,
        'ai_suggest'          => 1,
        'ai_column_summary'   => 1,
        'ai_enrich'           => 2,
        'ai_visualize'        => 2,
        'email_draft'         => 2,
        'lead_score'          => 1,
        'property_match'      => 1,
        'ai_summary'          => 1,
        'dashboard_insight'   => 2,
        'bulk_enrich_per_10'  => 5,
    ],
];
```

---

## 4. Credit Deduction Flow

```
User triggers AI action
    ↓
AiCreditService::check(user, action)
    ↓ BYOK?
    Yes → skip credit check → use user's own API key → log with byok=true
    No  → check org pool: credits_used + cost <= credits_total?
         AND (user has limit set?) → user credits_used + cost <= user credits_limit?
        → PASS → deduct credits → proceed → log usage
        → FAIL → return InsufficientCreditsException → frontend shows upgrade prompt
    ↓
AI call made
    ↓
AiCreditService::deduct(user, action, credits_used)  [idempotent, keyed by request_id]
```

**AiCreditService** methods:
```php
class AiCreditService {
    public function canUse(User $user, string $action): bool
    public function deduct(User $user, string $action): void
    public function getBalance(Organization $org): array  // ['total', 'used', 'remaining']
    public function getUserBalance(User $user): array
    public function resetPeriod(Organization $org): void  // called by scheduler on period_end
    public function addCredits(Organization $org, int $amount): void  // superadmin top-up
    public function getByokKey(User $user): ?string  // decrypted key or null
    public function setByokKey(User $user, string $provider, string $key, ?string $model): void
}
```

---

## 5. Frontend Credit Display

- **Credit widget**: shown in AppSidebar bottom (below nav items) — small pill showing `{remaining}/{total} AI credits`
- **Per-page**: DataTable HasAi NLQ bar shows "⚡ {remaining} credits" tooltip
- **Upgrade prompt**: when credits exhausted, `InsufficientCredits` modal with link to plan upgrade or BYOK setup
- **BYOK badge**: users with BYOK active see "🔑 Using your own key" instead of credit counter
- **Usage breakdown**: accessible at `/settings/ai-credits` — bar chart of usage by action type for current period

---

## 6. BYOK Setup Flow

User navigates to **Settings → AI → Bring Your Own Key**:
1. Select provider: OpenAI | OpenRouter
2. Enter API key (validated live via test call)
3. Optional: select preferred model (default from plan config)
4. Save → key stored encrypted in `ai_user_byok`
5. All AI actions for this user now use their key, credit counter hidden

**Validation**: on save, make a test call (`/v1/models` for OpenAI or `/api/v1/models` for OpenRouter) to verify the key is valid. Show error if invalid.

---

## 7. Superadmin Controls (Filament)

**Filament → Settings → AI Credits**:
- Set `default_credits_per_period` (applies to new plans and new orgs)
- Adjust credit costs per action (live-editable in DB, not just config)
- View credit usage analytics across all orgs (table + chart)
- Top-up credits for a specific org (one-time addition, does not affect plan)

**Filament → Plans → Edit Plan**:
- `ai_credits_per_period` field on each plan (default 100, editable)
- Changes apply to next billing period reset for existing subscribers

**Filament → Users → Edit User** (within org context):
- Set per-user credit limit (optional)
- View this user's credit usage this period
- Revoke BYOK if set

**Subscriber self-service (their Settings → Team → Edit Member)**:
- Set per-user credit limit for their team members
- View team AI usage breakdown

---

## 8. Scheduler

```php
// Runs daily at midnight — resets orgs whose period_end = today
Schedule::call(fn() => AiCreditResetJob::dispatch())->daily();
```

`AiCreditResetJob`: loops all `ai_credit_pools` where `period_end <= today`, creates new row for next period with `credits_total` from `org.plan.ai_credits_per_period`. Also resets `ai_user_credit_limits.credits_used = 0` for the new period.

---

## 9. Step Assignment

| Step | What to implement |
|---|---|
| **Step 0 (Task 1)** | Create all tables, `AiCreditService`, `config/ai-credits.php`, `AiCreditPool` + `AiCreditUsage` models |
| **Step 6 (Task 7)** | Wire `AiCreditService::check()` + `deduct()` into AI agent chat handler and all `handleAi*` DataTable methods |
| **Step 7 (Task 8)** | Wire dashboard insight deduction |
| **Step 23 (Task 18)** | Wire plan `ai_credits_per_period`, period reset scheduler, Filament superadmin UI, BYOK setup page, credit widget in sidebar |
