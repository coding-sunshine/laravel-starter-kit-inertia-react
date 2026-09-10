# PRD Run Order

> Feed PRDs to Chief in this order. Test between each.
> PRD numbers are stable — don't renumber (cross-references would break).
>
> **Launch strategy (2026-03-25):** Builder Portal ships FIRST. Subscriber migration later.
> See `v4/specs/2026-03-25-builder-portal-fast-track-design.md` for full design.

---

## Phase 1: Builder Portal (SHIP FIRST)

> Goal: Builders self-signup, create stock, agents browse portal. Stock auto-pushes to v3.
> Zero v3 code changes. Subscribers see builder stock as normal project/lot listings.

### Phase 1a: Foundation (full)

```
00-preflight-checklist.md    ← manual, do first
01-foundation.md             ← module setup, config, roles, seeders (FULL)
```

### Phase 1b: Models + Auth (slim — migrations + models only, NO v3 import)

```
02-contacts-and-users.md     ← users only: migrations, models, auth, self-signup
                               SKIP: contact import (9,735), user import (1,503)
04-projects.md               ← migrations + models only
                               SKIP: v3 import (15K projects, 121K lots)
05-sales.md                  ← migrations + models only
                               SKIP: v3 import (447 sales, 120 reservations)
03-sync.md                   ← SLIM: mysql_legacy connection + PackageV3PushObserver only
                               SKIP: bidirectional sync, pollers, field mappers
```

### Phase 1c: The Product

```
12-property-builder.md       ← TIER 1 ONLY: Construction Library, Packages, Distribution,
                               Workbench, Agent Portal, Brochures, v3 push, self-signup
                               THIS IS THE LAUNCH.
```

### Phase 1 SKIP list (deferred to Phase 2)

```
SKIP: 02 contact import (9,735 contacts)
SKIP: 02 user import (1,503 users)
SKIP: 03 full sync (bidirectional, pollers, field mappers)
SKIP: 04 project/lot import (15K + 121K rows)
SKIP: 05 sales import (447 + 120 rows)
SKIP: 06-remaining.md         ← notes, comments, partners, media — subscriber data
SKIP: 07-ai.md                ← AI agents, embeddings, credits
SKIP: 08-dashboards.md        ← subscriber dashboards + reports
SKIP: 12 Tier 2-3             ← saved searches, newsletters, AI push, analytics
```

---

## Phase 2: Subscriber Migration (after Builder Portal launched)

> Goal: Import all v3 data, enable full sync, switch subscribers from v3 to v4.

### Phase 2a: Data Import (complete the skipped parts of Phase 1)

```
02-contacts-and-users.md     ← NOW: import 9,735 contacts + 1,503 users
04-projects.md               ← NOW: import 15K projects + 121K lots (merge with builder data)
05-sales.md                  ← NOW: import 447 sales + 120 reservations
03-sync.md                   ← NOW: full bidirectional sync infrastructure
06-remaining.md              ← notes, comments, partners, media, resources
```

### Phase 2b: AI + Dashboards

```
07-ai.md                     ← AI agents + pgvector + embeddings + credits
08-dashboards.md             ← subscriber dashboards + reports
```

### Phase 2c: Subscriber cutover

```
Subscribers switch from v3 to v4.
v3 push observers removed.
v3 decommissioned.
```

---

## Phase 3: Advanced Features (after subscriber migration)

> Goal: Platform enhancements, new capabilities, market differentiation.

### Phase 3a: Builder Portal Tiers 2-3

```
12-property-builder.md       ← TIER 2: brochure gen, API, saved searches, newsletters
                               TIER 3: AI push, MLS, compliance, WordPress plugin, analytics
```

### Phase 3b: Platform Features (any order)

```
09-search.md                 ← Typesense full-text search
10-ai-lead-gen.md            ← AI lead generation
11-ai-core-voice.md          ← AI core + voice
13-analytics.md              ← CRM analytics
14-deal-tracker.md           ← deal pipeline
15-marketing-rebuild.md      ← marketing (rebuilt from scratch)
16-websites-rebuild.md       ← websites API (rebuilt from scratch)
17-xero.md                   ← Xero integration
18-signup-onboarding.md      ← subscriber signup + Stripe billing
19-rd-features.md            ← custom fields + automation
20-deferred.md               ← lead routing, AI follow-ups, co-selling
```

---

## Notes

- PRD numbers are stable — Phase 1/2/3 reorders execution, not numbering
- Phase 1 creates ALL migrations (schema complete) but skips v3 data imports
- Phase 2 import commands must handle merge with Phase 1 builder-created data
- PRD 12 is the product — everything else supports it or follows it
