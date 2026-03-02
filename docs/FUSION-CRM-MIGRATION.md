# Fusion CRM — Migration guide for developers

This guide lets any developer run the **Fusion CRM contact migration** (Step 1 of the rebuild): move contacts, emails, and phones from the legacy MySQL dump into this Laravel app (PostgreSQL).

---

## What gets migrated

| Legacy (MySQL)        | New (PostgreSQL)        |
|-----------------------|--------------------------|
| `leads`               | `contacts` (with `legacy_lead_id` = old lead id) |
| `contacts` (polymorphic, type=Lead) | `contact_emails`, `contact_phones` |
| `sources`             | `sources`               |
| `companies`           | `companies`             |

**Expected counts after a full run:** ~9,399 contacts, ~18,275 contact_emails + contact_phones, 17 sources, 969 companies.

---

## Prerequisites

- **PHP 8.4+** (Laravel app requirement)
- **MySQL** (server running; used only as source for the dump)
- **PostgreSQL** (server running; used as the app database)
- **Legacy SQL dump:** `fv3_2026-03-02.sql` (or equivalent full DB export)

---

## 1. Databases

### 1.1 MySQL (legacy source)

Start MySQL (e.g. Homebrew: `brew services start mysql`). Create a database and restore the dump:

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS fusion_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore dump (path to your .sql file)
mysql -u root -p fusion_legacy < /path/to/fv3_2026-03-02.sql
```

This can take 1–2 minutes for a large dump.

### 1.2 PostgreSQL (app database)

Start PostgreSQL (e.g. `brew services start postgresql@16`). Create the app database if it doesn’t exist:

```bash
createdb laravel
# or: psql -U your_user -d postgres -c "CREATE DATABASE laravel;"
```

---

## 2. Laravel app configuration

### 2.1 Environment

Copy `.env.example` to `.env` if needed, then set:

**PostgreSQL (default connection):**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password
```

**Legacy MySQL (for import):**

```env
MYSQL_LEGACY_HOST=127.0.0.1
MYSQL_LEGACY_PORT=3306
MYSQL_LEGACY_DATABASE=fusion_legacy
MYSQL_LEGACY_USERNAME=root
MYSQL_LEGACY_PASSWORD=
```

### 2.2 Install dependencies and run migrations

```bash
composer install
php artisan key:generate   # if APP_KEY is empty
php artisan migrate
```

Migrations create: `sources`, `companies`, `contacts`, `contact_emails`, `contact_phones` (and any other kit migrations).

---

## 3. Import contacts (full flow)

### Option A: One-shot full import (if it doesn’t stall)

```bash
php -d memory_limit=512M -d max_execution_time=0 artisan fusion:import-contacts --fresh
```

If the process **stops partway** (e.g. 20–70%), **do not** use `--fresh` again. Use the resume/batch options below.

### Option B: Resume (no `--fresh`)

Run again without `--fresh` so it continues from existing data:

```bash
php artisan fusion:import-contacts
```

Repeat until contacts count reaches 9,399 and the polymorphic step finishes.

### Option C: Import leads in batches (if it keeps stalling)

Import a range of lead IDs:

```bash
# Example: from lead id 6906 to end
php artisan fusion:import-contacts --from-id=6906 --chunk=100

# Or a small batch (e.g. 6906–8100, then 8101–end)
php artisan fusion:import-contacts --from-id=6906 --to-id=8100 --chunk=100
php artisan fusion:import-contacts --from-id=8101 --chunk=100
```

After **all 9,399 contacts** are in, sync only emails/phones:

```bash
php -d memory_limit=512M -d max_execution_time=0 artisan fusion:import-contacts --details-only
```

### Option D: Details only (contacts already imported)

When contacts are complete but email/phone counts are low:

```bash
php -d memory_limit=512M -d max_execution_time=0 artisan fusion:import-contacts --details-only
```

---

## 4. Command reference: `fusion:import-contacts`

| Option           | Description |
|------------------|-------------|
| `--fresh`        | Truncate fusion tables (sources, companies, contacts, contact_emails, contact_phones) then import. Use only for a clean start. |
| `--from-id=N`    | Only process leads with `id >= N`. Use to resume or run in batches. |
| `--to-id=N`      | Only process leads with `id <= N`. Combine with `--from-id` for a small batch. |
| `--chunk=N`      | Chunk size (default 200). Use 100 if the process stalls or runs out of memory. |
| `--skip-details` | Import only contacts; skip contact_emails/contact_phones. Run again without this to sync details. |
| `--details-only` | Only sync contact_emails/contact_phones from legacy. Contacts must already be imported. |
| `--force`        | Continue even if legacy MySQL connection fails (not recommended). |

**Tips:**

- For long runs: `php -d memory_limit=512M -d max_execution_time=0 artisan fusion:import-contacts ...`
- The command is **idempotent**: safe to run multiple times (uses `updateOrCreate` / `firstOrCreate` by legacy id).

---

## 5. Verify import

```bash
php artisan fusion:verify-import-contacts
```

**Pass criteria:**

- **contacts** = legacy leads count (e.g. 9,399)
- **sources** = 17, **companies** = 969
- **contact_emails + contact_phones** ≥ 90% of legacy polymorphic `contacts` count (some rows are skipped: empty value or orphan `model_id`)

If legacy MySQL is not configured, the command still prints PostgreSQL counts and shows “N/A” for legacy.

---

## 6. Seed CRM roles (optional)

```bash
php artisan db:seed --class=Database\\Seeders\\Essential\\CrmRolesSeeder
```

Creates permissions (`view contacts`, `create contacts`, etc.) and roles (`sales-agent`, `bdm`, `referral-partner`).

---

## 7. Troubleshooting

| Issue | What to do |
|-------|------------|
| Import stops at 20–70% | Don’t use `--fresh`. Run again to resume, or use `--from-id=<last_id+1>` and smaller `--chunk=100`. |
| “Legacy MySQL connection failed” | Check MySQL is running and `.env` has correct `MYSQL_LEGACY_*`. Restore the dump into `MYSQL_LEGACY_DATABASE`. |
| Out of memory / process killed | Use `php -d memory_limit=512M` and `--chunk=100`. For details-only step use `--details-only` with higher memory. |
| Contacts complete but few emails/phones | Run `php -d memory_limit=512M artisan fusion:import-contacts --details-only`. |
| Verify fails on contact_emails+contact_phones | Up to ~10% of legacy rows are skipped (empty value or orphan). Verification passes if new count ≥ 90% of legacy. |

---

## 8. For the next step (Step 2)

The mapping **old `leads.id` → new `contacts.id`** is in **`contacts.legacy_lead_id`**. Step 2 (users and contact link) will use this to set `users.contact_id`. See the rebuild plan in `rebuild-plan/` (e.g. `03-step-2-users-and-contact-link.md`).

---

## Quick checklist (new developer)

1. [ ] MySQL running; database created; dump restored into it.
2. [ ] PostgreSQL running; app database (e.g. `laravel`) created.
3. [ ] `.env` has `DB_*` (PostgreSQL) and `MYSQL_LEGACY_*` (legacy DB).
4. [ ] `composer install` and `php artisan migrate` run successfully.
5. [ ] `php artisan fusion:import-contacts` run (with resume/batch options if needed).
6. [ ] `php artisan fusion:import-contacts --details-only` if email/phone counts were low.
7. [ ] `php artisan fusion:verify-import-contacts` passes.
8. [ ] Optionally: `php artisan db:seed --class=Database\\Seeders\\Essential\\CrmRolesSeeder`.
