# Fusion CRM Rebuild — Step 1: Contacts and complete data

This document describes how to get **complete contact data** from the legacy MySQL dump (`fv3_2026-03-02.sql`) into the Laravel Starter Kit (PostgreSQL).

**→ For a full developer-facing guide (prerequisites, DB setup, all import options, troubleshooting), see [FUSION-CRM-MIGRATION.md](./FUSION-CRM-MIGRATION.md).**

## Prerequisites

- Laravel app using **PostgreSQL** as default DB (`DB_CONNECTION=pgsql`).
- Legacy data: restore the MySQL dump into a MySQL instance so the import command can read from it.

## 1. Run migrations

```bash
php artisan migrate
```

This creates: `sources`, `companies`, `contacts`, `contact_emails`, `contact_phones`.

## 2. Configure legacy MySQL

In `.env` set the connection to your **restored** legacy database (the one you restored from `fv3_2026-03-02.sql`):

```env
MYSQL_LEGACY_HOST=127.0.0.1
MYSQL_LEGACY_PORT=3306
MYSQL_LEGACY_DATABASE=your_restored_db_name
MYSQL_LEGACY_USERNAME=root
MYSQL_LEGACY_PASSWORD=
```

Restore the dump first, e.g.:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS fusion_legacy;"
mysql -u root -p fusion_legacy < /path/to/fv3_2026-03-02.sql
```

Then set `MYSQL_LEGACY_DATABASE=fusion_legacy` (or the name you used).

## 3. Import contacts (complete data)

For a **full import in one go** (recommended if you have time):

```bash
php -d memory_limit=512M -d max_execution_time=0 artisan fusion:import-contacts --fresh
```

If the command **stops partway** (e.g. at 20%), do **not** use `--fresh` again. Just run:

```bash
php artisan fusion:import-contacts
```

This will **resume**: add any missing contacts, then sync all contact_emails and contact_phones. You can run it repeatedly until verification passes.

Normal run (no options):

```bash
php artisan fusion:import-contacts
```

This:

- Imports **sources** and **companies** from MySQL into PostgreSQL.
- Imports every **lead** as a **contact** (with `legacy_lead_id` = old `leads.id` for the map).
- Imports the polymorphic **contacts** (emails/phones attached to Lead) into `contact_emails` and `contact_phones`.

Expected approximate counts (from plan): **contacts** ≈ 9,678, **contact_emails** + **contact_phones** ≈ 20,074, **sources** = 17, **companies** = 969.

## 4. Verify import

```bash
php artisan fusion:verify-import-contacts
```

Compares row counts with the legacy DB. If legacy is not configured, it still shows new counts and “N/A” for legacy.

## 5. Seed CRM roles (optional)

```bash
php artisan db:seed --class=Database\\Seeders\\Essential\\CrmRolesSeeder
```

Creates permissions (`view contacts`, `create contacts`, etc.) and roles (`sales-agent`, `bdm`, `referral-partner`).

## Lead → Contact map

The mapping from old `leads.id` to new `contacts.id` is stored in **`contacts.legacy_lead_id`**. Step 2 (users and contact link) will use this to set `users.contact_id`.

## Human-in-the-loop (after Step 1)

- [ ] Confirm migrations for contacts, contact_emails, contact_phones, sources, companies ran.
- [ ] Confirm `fusion:import-contacts` completed without fatal errors.
- [ ] Confirm verification PASS (or manual count: contacts ≈ 9,678, contact details ≈ 20,074).
- [ ] Confirm `contacts.legacy_lead_id` is populated for use in Step 2.
- [ ] Approve proceeding to Step 2 (users and contact link).
