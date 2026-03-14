# Two-Way Sync (Every 30 Minutes) — Optional / Coexistence

## Goal

Provide **two-way sync** between the **legacy MySQL** (Fusion v3 / Herd replica) and the **new PostgreSQL** (rebuilt Fusion CRM) so that **updated data** in either database is reflected in the other **every 30 minutes**. This supports coexistence (e.g. legacy app still writing to MySQL while the new app runs on PostgreSQL) or staged cutover.

**Prerequisites:** Steps 0–5 complete (one-time import done; lead_id↔contact_id and other ID maps exist). The `mysql_legacy` connection and mapping tables (or columns like `legacy_lead_id`) must be in place.

## Starter Kit References

- **Laravel Scheduler**: `app/Console/Kernel.php` or `routes/console.php` — `Schedule::command(...)->everyThirtyMinutes()`. For local/dev without cron: **spatie/laravel-cronless-schedule**. See 00-kit-package-alignment.md.
- **Queue**: Optional — run sync as queued jobs to avoid long-running requests
- **Database**: `config/database.php` — `pgsql` (default) and `mysql_legacy` connections

## Deliverables

1. **Sync schedule**
   - Register a command (e.g. `fusion:sync-two-way`) to run **every 30 minutes** via the Laravel scheduler (`Schedule::command('fusion:sync-two-way')->everyThirtyMinutes()`). Ensure `php artisan schedule:work` (or cron `* * * * * php artisan schedule:run`) is active in the environment.

2. **MySQL → PostgreSQL (pull)**
   - **Incremental pull**: For each synced table, read from `mysql_legacy` rows where `updated_at` (or equivalent) > last sync timestamp. Apply the same mapping rules as the one-time import (lead_id→contact_id, etc.) and **upsert** into PostgreSQL (update existing or insert new). Use the existing lead_id→contact_id (and project_id, lot_id, sale_id, etc.) maps so new or updated MySQL rows are applied to the correct PostgreSQL rows.
   - **Tables to sync**: Define scope (e.g. leads→contacts, contact details, users, projects, lots, property_reservations, sales, tasks, notes, etc.). Prefer a config or list so tables can be enabled/disabled.
   - **Change detection**: Rely on `updated_at` (and optionally `created_at`) on the MySQL side; persist **last_sync_at** per table or globally in a small `sync_state` table so the next run only fetches changes since then.

3. **PostgreSQL → MySQL (push)**
   - **Incremental push**: For each synced table, read from PostgreSQL rows where `updated_at` > last push timestamp. Map **reverse** (contact_id→lead_id, and other FKs) and **upsert** into MySQL (update legacy row or insert if new). Writing to MySQL requires the legacy schema (e.g. `leads` table, polymorphic `contacts` for email/phone). New rows created only in PostgreSQL need a **reverse map** (contact_id→lead_id) and possibly creating a new lead in MySQL for new contacts.
   - **Conflict resolution**: Define a rule (e.g. **last-write-wins** by `updated_at`, or **PostgreSQL wins** for fields updated in the new app). Document the rule and apply consistently.
   - **Tables to sync**: Same scope as pull, or a subset (e.g. only contacts and contact_emails/contact_phones if legacy app only needs person data). Reverse mapping must be maintained (e.g. store contact_id→legacy_lead_id when creating a lead from a contact).

4. **Idempotency and safety**
   - Use transactions where possible; avoid duplicate inserts (use unique keys or upsert by business key / mapped id). Log errors (e.g. failed rows, constraint violations) to a log or `sync_log` table for inspection.
   - **Rate / load**: 30 minutes is usually enough gap to avoid constant load; if tables are very large, consider chunking or limiting rows per run and processing in batches.

5. **Commands**
   - `fusion:sync-two-way` — run full two-way sync (pull then push, or configurable order).
   - Optional: `fusion:sync-pull` (MySQL→PostgreSQL only) and `fusion:sync-push` (PostgreSQL→MySQL only) for testing or one-direction-only use.

## DB Design (this step)

- **sync_state** (optional): id, connection_direction (e.g. 'mysql_to_pgsql', 'pgsql_to_mysql'), table_name, last_synced_at, last_synced_id (optional), metadata (json). Used to store last sync timestamp (and optionally last processed id) per table/direction so the next run is incremental.
- **sync_log** (optional): id, direction, table_name, row_id (or business key), status (success/failed), message, created_at. For debugging and audit.
- Existing **mapping** (e.g. contacts.legacy_lead_id, or import_mappings table) must be kept updated when new rows are created in PostgreSQL from MySQL or in MySQL from PostgreSQL, so reverse lookups work.

## Data Import

- **None** for one-time load. This step is **ongoing sync** only. The one-time import remains Steps 1–5.

## AI Enhancements

- None required. Optional: use Laravel AI or Prism to summarize sync results (e.g. "Synced 12 contacts, 3 sales; 0 conflicts") for a dashboard widget or notification.

## Verification (verifiable results)

- **Manual**: Change a row in MySQL (e.g. update a lead's name), run `php artisan fusion:sync-two-way`, then confirm the change appears in PostgreSQL (contact). Change a row in PostgreSQL, run sync, confirm the change appears in MySQL.
- **Automated**: After a sync run, assert that counts or checksums for synced tables (or a sample of rows) match expectations; or that `sync_log` has no failed rows for a test run.

## Human-in-the-loop (end of step)

**STOP after this step. Confirm sync works before relying on it in production.**

Human must:
- [ ] Confirm scheduler runs `fusion:sync-two-way` every 30 minutes (or that the command is registered and tested manually).
- [ ] Confirm MySQL → PostgreSQL: an update in MySQL appears in PostgreSQL after sync.
- [ ] Confirm PostgreSQL → MySQL: an update in PostgreSQL appears in MySQL after sync.
- [ ] Confirm conflict resolution rule is documented and behavior is as expected.
- [ ] Approve using two-way sync for coexistence or cutover, or list follow-ups (e.g. add more tables, tune interval).

## Acceptance Criteria

- [ ] Command `fusion:sync-two-way` runs (manually or via scheduler) and performs incremental pull (MySQL→PostgreSQL) and push (PostgreSQL→MySQL) for configured tables.
- [ ] Sync runs every 30 minutes via Laravel scheduler when the app is deployed.
- [ ] Last-sync state is persisted so each run only processes updated data.
- [ ] Conflict resolution rule is defined and applied; sync log or error handling is in place for debugging.
