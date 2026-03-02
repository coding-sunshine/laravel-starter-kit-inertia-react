# AI Phase 2 – Overview (AI-first deepening)

This document describes how Phase 2 deepens the **AI-first** position of the Fleet Intelligence Platform without replacing the existing AI implementation. It aligns with **phase-2-00-OVERVIEW.md** and the step-by-step Phase 2 files.

**Prerequisites:** Fleet Assistant and RAG (and any existing AI jobs) are already in place. Phase 2-01 and 2-02 are the main drivers for AI improvements.

---

## What Phase 2 adds on top of existing AI

| Existing AI | Phase 2 deepening |
|-------------|-------------------|
| **Fleet Assistant** (conversational, RAG, tools, streaming) | More tools (work orders, compliance, service schedules, defects, routes, alerts); clearer RAG citation; proactive insights and suggested questions; contextual “Ask assistant” from vehicle/driver/work order show. |
| **Document chunks / RAG** | Confirm all relevant documents chunked and embedded; one “search fleet documents” tool; agent instructed to cite sources; optional “expiring soon” or time-scoped answers. |
| **AI job runs** (predictive maintenance, fraud, compliance) | Visible in UI (list + show); results summarized and linked to entities; optional “Recent AI insights” on dashboard. |
| **Electrification / fleet optimization** | Results clearly presented; linked from dashboard or nav; optional assistant summary of plan or optimization result. |
| **Incident / claims** (damage assessment, FNOL, etc.) | No change required for Phase 2 unless Phase 2-02 adds an “Ask about this incident/claim” context. |

---

## Principles (unchanged)

- **Laravel AI SDK** (or equivalent): Agents, tools, RAG (FileSearch or custom over `document_chunks`), streaming, queue for heavy jobs. No swap of stack; extend only.
- **Organization scope:** Every tool and RAG query scoped by `organization_id`. Proactive insights and suggestions respect the same scope.
- **Conversation memory:** Existing `agent_conversations` / `agent_conversation_messages` (or RemembersConversations) remain; Phase 2 improves UX (rename, delete, no duplicate text) and adds context (e.g. `?context=vehicle:123`).
- **Structured output and jobs:** AI job runs and analysis results stay in `ai_analysis_results` and `ai_job_runs`; Phase 2 focuses on visibility and narrative, not new job types (unless a step explicitly adds one).

---

## Order of work (AI-related)

1. **Phase 2-01** – Fix assistant UX (dialogs, errors, tool coverage audit).
2. **Phase 2-02** – Add tools (work orders, compliance, service, defects, routes, alerts); RAG citation; proactive insights; contextual “Ask assistant” from show pages.
3. **Phase 2-03** – Dashboard and reports (optional “AI insights” card; report run/download).
4. **Phase 2-04** – Compliance/safety/electrification visibility; AI job runs and analysis results visible.
5. **Phase 2-05** – Demo narrative and one-pager (assistant and AI as hero).

No separate “AI-01 through AI-07” re-implementation; Phase 2 steps above are the single place for AI-first enhancements. For historical reference, the original AI steps (AI-01 fleet assistant RAG, AI-02 document intelligence, AI-03 computer vision, etc.) remain conceptually valid; Phase 2 chooses to deepen assistant and visibility first for the investor demo.

---

## Schema references (unchanged)

- **document_chunks** – RAG; `chunkable_type`/`chunkable_id`, `content`, `embedding`, `organization_id`, source metadata. See FLEET_FINAL_DATABASE_SCHEMA.md § 3.26.
- **ai_analysis_results** – Polymorphic `entity_type`/`entity_id`, `analysis_type`, `primary_finding`, `detailed_analysis`, `recommendations`, `priority`, `status`. § 3.30.
- **ai_job_runs** – `job_type`, `entity_type`/`entity_ids`, `status`, `result_data`, `laravel_job_id`. § 3.32.
- **agent_conversations** / **agent_conversation_messages** – Conversation history; scope to fleet/org.

After Phase 2, the platform is clearly AI-first: one assistant, many tools, RAG with citation, proactive insights, and visible AI job results—all documented in the phase-2 step files and this overview.
