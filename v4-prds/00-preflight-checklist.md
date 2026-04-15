# Pre-Flight Checklist

> **Purpose:** Manual setup steps before any PRD can begin. Complete all items in order.
> **Time estimate:** 15-30 minutes
> **CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

---

## 1. Clone Starter Kit

- [ ] Copy starter kit to new project directory:
  ```bash
  cp -r /Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react /path/to/fusioncrm-v4
  cd /path/to/fusioncrm-v4
  ```

## 2. Install Dependencies

- [ ] Install PHP and JS dependencies:
  ```bash
  composer install && bun install && bun run build
  ```

## 3. Configure Environment

- [ ] Copy the v4 env template as a starting point:
  ```bash
  cp /Users/apple/Code/clients/piab/fusioncrmv3/v4/.env.v4-template /path/to/fusioncrm-v4/.env
  ```
- [ ] Fill in the database credentials (at minimum: `DB_PASSWORD`, `DB_LEGACY_PASSWORD`)
- [ ] Other API keys can be added later as each PRD requires them

## 4. Verify NTP (Required for Sync Clock Alignment)

- [ ] Run one of:
  ```bash
  ntpstat || timedatectl show-ntp
  ```
  Confirm time synchronization is active.

## 5. Run Starter Kit Migrations

- [ ] Generate app key and run migrations:
  ```bash
  php artisan key:generate
  php artisan migrate
  ```
- [ ] Verify app loads:
  ```bash
  php artisan serve
  # Visit http://localhost:8000 — should return 200
  ```

## 6. Add Legacy MySQL Connection

- [ ] Add `mysql_legacy` connection to `config/database.php` under `'connections'`:
  ```php
  'mysql_legacy' => [
      'driver' => 'mysql',
      'host' => '127.0.0.1',
      'port' => '3306',
      'database' => 'fv3',
      'username' => 'root',
      'password' => '',
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
  ],
  ```

## 7. Test Legacy Connection

- [ ] Verify legacy database is accessible:
  ```bash
  php artisan tinker --execute="echo DB::connection('mysql_legacy')->table('leads')->count()"
  ```
  **Expected:** `9735`

---

## 8. Copy v4 Planning Files to Project

- [ ] Copy CLAUDE.md to v4 project root (read automatically by Claude Code every session):
  ```bash
  cp /Users/apple/Code/clients/piab/fusioncrmv3/v4/CLAUDE.md /path/to/fusioncrm-v4/CLAUDE.md
  ```
- [ ] Copy schema docs (referenced by PRDs for DDL and sync details):
  ```bash
  cp -r /Users/apple/Code/clients/piab/fusioncrmv3/v4/schema/ /path/to/fusioncrm-v4/v4-schema/
  ```
- [ ] Verify files exist:
  ```bash
  ls /path/to/fusioncrm-v4/CLAUDE.md            # universal context
  ls /path/to/fusioncrm-v4/v4-schema/newdb.md   # full DDL
  ls /path/to/fusioncrm-v4/v4-schema/newdb-simple.md   # column map
  ls /path/to/fusioncrm-v4/v4-schema/sync-architecture.md  # sync design
  ls /path/to/fusioncrm-v4/v4-schema/dbissues.md  # v3 issues
  ```

---

## 9. Verify Environment Variables

Review ALL keys in `.env`. Check which ones you have NOW vs which ones you'll add later.

### Required NOW (PRDs 01-08 won't work without these)

- [ ] `DB_CONNECTION=pgsql` — PostgreSQL
- [ ] `DB_DATABASE=fusioncrm_v4` — v4 database name
- [ ] `DB_USERNAME` + `DB_PASSWORD` — PostgreSQL credentials
- [ ] `DB_LEGACY_DATABASE=fv3` — v3 MySQL database
- [ ] `DB_LEGACY_USERNAME` + `DB_LEGACY_PASSWORD` — v3 MySQL credentials
- [ ] `SYNC_QUEUE=sync` — dedicated sync queue
- [ ] `SYNC_PIAB_ORG_ID=1` — PIAB org ID
- [ ] `SYNC_TIEBREAKER=v3` — conflict resolution default

### Required for AI (PRD 07+)

- [ ] `OPENAI_API_KEY` — **Do you have this?** Required for all AI features.
- [ ] `THESYS_API_KEY` — Optional. Generative UI for AI responses. Plain text fallback without it.
- [ ] `DATA_TABLE_AI_MODEL=gpt-4o-mini` — Model for DataTable NLQ queries.

### Required for Search (PRD 09)

- [ ] `TYPESENSE_API_KEY` — **Do you have this?** Required for full-text search.
- [ ] `TYPESENSE_HOST=localhost` + `TYPESENSE_PORT=8108`

### Required for Real-time (PRD 09, optional)

- [ ] `REVERB_APP_ID` + `REVERB_APP_KEY` + `REVERB_APP_SECRET` — Optional. Real-time DataTable updates.

### Required for Billing (PRD 18)

- [ ] `STRIPE_KEY` — **Do you have this?** Publishable key for subscriptions + one-time credits + deposits.
- [ ] `STRIPE_SECRET` — Secret key.
- [ ] `STRIPE_WEBHOOK_SECRET` — Webhook signing secret.
- [ ] Note: Stripe handles ALL payments. No eWAY, no Lemon Squeezy.

### Required for Xero (PRD 17)

- [ ] `XERO_CLIENT_ID` + `XERO_CLIENT_SECRET` — **Do you have this?** Required for accounting integration.

### Required for Email (PRD 15)

- [ ] `MAIL_HOST` + `MAIL_USERNAME` + `MAIL_PASSWORD` — SMTP credentials for sending emails.

### Required for Error Tracking (PRD 01, optional for dev)

- [ ] `SENTRY_DSN` — Optional for development. Required for production.

**Action:** For any key you don't have yet, note it down. The agent will ASK you for it when that PRD starts. You can skip keys for later PRDs and add them when you get there.

---

## All Clear?

When every checkbox above is checked, you have:
- v4 app running at localhost:8000
- Legacy MySQL connection working
- CLAUDE.md in project root (agent reads automatically)
- Schema docs in v4-schema/ (agent references for DDL)
- All required env vars set (or noted for later)

Proceed to **PRD 01: Foundation** → feed to Chief or Claude Code.
