# Production Release — feature/laravel-13

Steps to apply when merging `feature/laravel-13` → `railway` and releasing to production.

> **Just testing locally?** Jump to [§ L — Local testing](#l-local-testing) — a smaller subset of these steps is enough.

---

## L. Local testing

Use this when you're trying the branch on `https://rrmanagementlatest.test/` (or any local Herd site) before merging to `railway`. Production sections 3, 5, 6, 8 are **optional** locally — Loadrite, Reverb, and the cron scheduler can stay off unless you're specifically testing those features.

### L.1 Required steps

```bash
composer install                 # applies the Machour patch (patches/) automatically
npm install
npm run build                    # or `npm run dev` for HMR
php artisan migrate              # runs the 10 new migrations
php artisan db:seed --class='Database\Seeders\Essential\CommodityUtilisationThresholdSeeder'
php artisan optimize:clear       # flush route + config caches after pulling new code
php artisan wayfinder:generate   # refresh typed FE routes after route changes
```

### L.2 Queue worker (only if testing reconciliation jobs)

`ReconcilePenaltyHeadsJob` runs on the `penalties` queue. Either:

```bash
php artisan queue:work --queue=penalties,default      # one-shot worker
# OR if Horizon is configured locally:
php artisan horizon
```

If `QUEUE_CONNECTION=sync` in your `.env`, jobs run in-process and you can skip the worker.

### L.3 Optional features

| Feature | What to start | Skip if |
|---|---|---|
| Loadrite live polling | `php artisan loadrite:store-token --siding=2` then `php artisan loadrite:start-polling` | You're not testing Loadrite |
| Reverb / siding monitor | `php artisan reverb:start --port=8080` | You won't visit `/sidings/{id}/monitor` (browser console will show WS errors otherwise — non-blocking) |
| Scheduled jobs | `php artisan schedule:work` | You're not testing demurrage check / weekly report scheduling |

### L.4 Smoke check

After L.1, hit these URLs as a super-admin user (seeded as `superadmin@rmms.local` / `password`):

| URL | Expected |
|---|---|
| `/dashboard` | Dark navy header, pill nav, 6 sections |
| `/penalties` | Penalties table loads (was 500 pre-Machour-patch) |
| `/rakes` | Rakes table loads |
| `/admin/penalty-reconciliations` | Filament admin, table renders (rows depend on data) |
| `/admin/commodity-utilisation-thresholds` | 6 seeded rows |
| `/sidings/1/quick-placement` | Pakur placement page (need a siding-attached user with `siding_in_charge` role) |
| `/reconciliation/power-plant-receipts` | Loads (was 500 pre-route-reorder) |

If any of those throw a 500, run `php artisan optimize:clear` and check `storage/logs/laravel.log`.

### L.5 Full QA pass

When you're ready to confirm the whole release locally, run through `docs/e2e-testing-guide.md` — sections A–G cover every user-visible change.

---

## 0. Major version jumps in this release ⚠️

This branch includes major framework upgrades. Confirm the production server can run them before merging:

| Package | railway | feature/laravel-13 | Notes |
|---|---|---|---|
| `laravel/framework` | ^12.52 | ^13.7 | Major. PHP **8.4** required. |
| `inertiajs/inertia-laravel` | ^2.0 | ^3.0 | Major. Frontend rebuild required (already in §1). Axios removed; uses built-in XHR. |
| `filament/filament` | ^5.2 | ^5.6 | Minor. Re-publish assets (handled by `optimize:clear` + `view:cache` below). |

**Pre-flight on the server:**

```bash
php -v   # must be 8.4+
redis-cli ping   # must return PONG (Horizon + cache)
node -v  # must be 20+ for Vite/Tailwind v4
```

Any failure here → fix before deploy.

---

## 1. Standard deploy steps

Run these on the production server after the merge:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 2. Database migrations

Ten new migrations ship in this branch. `migrate --force` above runs them in timestamp order, but listed here for awareness:

| Migration | What it does |
|-----------|-------------|
| `2026_04_29_120000_upgrade_activity_log_table_for_spatie_activity_log_v5` | Spatie ActivityLog v5 schema upgrade |
| `2026_04_29_142818_create_loading_overrides_table` | PCC loading overrides log |
| `2026_04_29_220306_create_loadrite_settings_table` | Stores encrypted Loadrite API tokens per siding |
| `2026_04_29_220534_add_loadrite_columns_to_wagon_loading_table` | `loadrite_weight_mt`, `weight_source`, `loadrite_last_synced_at`, `loadrite_override` on `wagon_loading` |
| `2026_04_30_000001_add_migration_columns_to_penalties_table` | `migrated_at`, `migration_note` on `penalties` |
| `2026_04_30_055618_add_site_name_to_loadrite_settings` | `site_name` column (required for API calls) |
| `2026_05_01_000001_create_penalty_reconciliations_table` | Predicted-vs-billed reconciliation per penalty head (Stage 1) |
| `2026_05_01_000002_create_commodity_utilisation_thresholds_table` | PLO utilisation thresholds per commodity grade (Stage 1) |
| `2026_05_01_130333_add_commodity_grade_to_rakes_table` | `commodity_grade` on rakes — required by PLO calculator (Stage 1) |
| `2026_05_01_152956_add_index_to_rr_penalty_snapshots_rake_id` | FK index for reconciliation aggregation perf (Stage 1) |

---

## 2.5 Seeders — Stage 1

Run after migrations to populate PLO utilisation thresholds (G1–G5 + UNGRADED at 0.95 each). Without this, `CalculatePloPenaltyAction` falls back to a hardcoded 0.95 default — functional but the Filament admin at `/admin/commodity-utilisation-thresholds` will be empty.

```bash
php artisan db:seed --class='Database\Seeders\Essential\CommodityUtilisationThresholdSeeder' --force
```

Idempotent: re-running on existing data updates rather than duplicates (uses `updateOrCreate` keyed on `commodity_grade + effective_from`).

---

## 3. Loadrite token setup — ONE-TIME ⚠️

This must be done once after deploy. Tokens live in the database (encrypted), **not** in `.env`.

**Step 1 — confirm the site name from the API:**

```bash
curl -H "Authorization: Bearer <ACCESS_TOKEN>" \
  https://apicloud.loadrite-myinsighthq.com/api/v2/context/get-sites
# Expected: ["Dumka railway siding"]
```

**Step 2 — store tokens for Dumka siding (siding_id = 2):**

```bash
php artisan loadrite:store-token --siding=2
```

When prompted:
- **Site name:** `Dumka railway siding`
- **Access token:** paste from myinsighthq.com portal → API Keys
- **Refresh token:** paste from portal
- **Expiry:** from the portal (e.g. `2027-04-30 10:22:00`)

> Tokens auto-refresh when expired — no manual renewal needed once stored.

---

## 4. Horizon — restart and verify queues

`config/horizon.php` already ships with the correct supervisor-1 queue list:

```php
'queue' => ['loadrite-poll', 'loadrite-sync', 'loadrite-alerts', 'penalties', 'default'],
```

No manual edit needed — `composer install` + the merged config takes care of it. Restart Horizon to pick up the new queues:

```bash
php artisan horizon:terminate
# Let your supervisor/process manager restart Horizon automatically
```

Verify the new queues appear in the Horizon dashboard (`/horizon`):
- `loadrite-poll`
- `loadrite-sync`
- `loadrite-alerts`
- `penalties` ← new in Stage 1, must be present

---

## 5. Start Loadrite polling

The scheduler runs `loadrite:start-polling` every 5 minutes automatically. To start immediately after deploy without waiting:

```bash
php artisan loadrite:start-polling
```

This dispatches the self-scheduling `PollLoadriteJob` for each siding that has tokens stored. After this, polling runs every 30 seconds via Horizon with no further intervention.

**Verify it's working:**

```bash
php artisan tinker --execute '
$s = App\Models\LoadriteSetting::where("siding_id", 2)->first();
echo "site: " . $s->site_name . PHP_EOL;
echo "expires: " . $s->expires_at . PHP_EOL;
echo "token ok: " . (strlen($s->access_token) > 10 ? "yes" : "NO") . PHP_EOL;
'
```

---

## 6. Laravel Reverb — WebSocket server

The real-time siding monitor (`/sidings/2/monitor`) requires Reverb to be running. Confirm it is up:

```bash
php artisan reverb:start --port=8080
# Or check your supervisor config has it running
```

The new private channel `siding.{sidingId}` is authenticated via `routes/channels.php` — no additional config needed.

---

## 7. Penalty recalculation (conditional)

If historical penalty amounts appear incorrect in production (wrong demurrage formula was running before this branch), recalculate:

```bash
# Dry run first — shows what would change without writing
php artisan penalties:recalculate --dry-run

# Recalculate all penalties (safe to run, uses DB transaction)
php artisan penalties:recalculate

# Or for a specific rake only
php artisan penalties:recalculate --rake=<rake_id>
```

---

## 8. New scheduler jobs (already wired, no action needed)

These run automatically via the scheduler — listed for awareness:

| Schedule | Command |
|----------|---------|
| Every 5 min | `loadrite:start-polling` — watchdog that ensures polling is active |
| Every 5 min | `rrmcs:check-demurrage` — checks loading rakes for threshold crossings |
| Monday 06:00 | `rrmcs:generate-penalty-insights` — AI penalty analysis |
| Monday 08:00 | `rrmcs:send-weekly-penalty-report` — emails penalty report to admins |

Confirm the scheduler cron is registered on the server:

```bash
crontab -l | grep artisan
# Should show: * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. New routes

| Route | Purpose | Access |
|---|---|---|
| `/sidings/{siding}/monitor` | Real-time siding monitor (Reverb-driven) | Authenticated users — no permissions gate; add middleware/gate before go-live if restriction needed |
| `/admin/penalty-reconciliations` | Filament — predicted-vs-billed reconciliation list (read-only) | Admin panel users |
| `/admin/commodity-utilisation-thresholds` | Filament — PLO utilisation thresholds (CRUD) | Admin panel users |
| `/sidings/{siding}/quick-placement` | Mobile-friendly placement capture (Stage 1, primarily for Pakur data hole) | Authenticated user attached to the siding (`user_siding` pivot) with `siding_in_charge` or `siding_operator` role |

---

## 10. Post-deploy smoke test

```bash
# 1. Migrations ran cleanly
php artisan migrate:status | grep -E "loadrite|loading_override|penalties.*migrat"

# 2. Loadrite token is stored
php artisan tinker --execute 'echo App\Models\LoadriteSetting::count() . " settings stored\n";'

# 3. Loadrite API responds
php artisan tinker --execute '
$m = app(App\Services\LoadriteTokenManager::class);
$c = $m->getConnector(2);
$r = $c->send(new App\Http\Integrations\Loadrite\Requests\GetNewWeightEventsRequest(
    "Dumka railway siding",
    now()->subHour()->format("Y-m-d H:i:s"),
    now()->format("Y-m-d H:i:s")
));
echo "API status: " . $r->status() . "\n";  // 200 or 204 = good
'

# 4. Horizon queues visible
php artisan horizon:status

# 5. Build assets present
ls -la public/build/assets/ | tail -5

# 6. Stage-1 tables exist
php artisan tinker --execute '
echo "penalty_reconciliations: " . (Schema::hasTable("penalty_reconciliations") ? "✓" : "✗") . PHP_EOL;
echo "commodity_utilisation_thresholds: " . (Schema::hasTable("commodity_utilisation_thresholds") ? "✓" : "✗") . PHP_EOL;
echo "rakes.commodity_grade: " . (Schema::hasColumn("rakes", "commodity_grade") ? "✓" : "✗") . PHP_EOL;
'

# 7. PLO thresholds seeded (expect 6 rows)
php artisan tinker --execute 'echo App\Models\CommodityUtilisationThreshold::count() . " thresholds (expect 6)" . PHP_EOL;'

# 8. Reconciliation listeners auto-discovered
php artisan event:list | grep -E "AppliedPenaltyPersisted|RrPenaltySnapshotsImported"
# Expected: both events listed with their listener mappings (ReconcileOnAppliedPenalty, ReconcileOnRrImport)
```

---

## Rollback

If anything goes wrong:

```bash
# Roll back all 10 new migrations in reverse order
php artisan migrate:rollback --step=10

# Clear all caches
php artisan optimize:clear
```

> The `loadrite_settings` table rows are not touched by rollback — delete manually if needed:
> ```sql
> DELETE FROM loadrite_settings WHERE siding_id = 2;
> ```
>
> The `penalty_reconciliations` table is dropped by rollback (additive table, no FK dependents). The `commodity_utilisation_thresholds` table is also dropped. The `rakes.commodity_grade` column is dropped. No data outside these new tables/columns is affected.

---

## 12. Calibration test — expected CI red

`tests/Feature/Calibration/RrReconciliationCalibrationTest.php` is an intentional merge gate. It fails until at least one **real** RR-derived fixture is added to `tests/Fixtures/RailwayBills/` (synthetic placeholders are rejected). CI on `railway` and downstream branches will show 1 red test until then.

**To unblock and flip the gate green:**
1. Pick a Pakur rake whose RR document is on file (`rr_documents` table) and that has DEM/PLO bills (`rr_penalty_snapshots`).
2. Use `php artisan pakur:backfill-placement` (Section 13) to set its `placement_time` and `loading_end_time` from the siding logbook.
3. Read the rake's wagon weighments + RR bill values, capture as JSON in `tests/Fixtures/RailwayBills/<date>-<siding>-<head>-<seq>.json` per the schema in that directory's `README.md`. Set `"synthetic": false`.
4. Run `composer test:calibration` — must pass within ±10% predicted-vs-billed.

Until then, treat this single failure as expected.

---

## 13. `pakur:backfill-placement` — operational artisan

For closing the historical Pakur data hole. Imports `placement_time` and `loading_end_time` onto existing rakes from a CSV export of the siding logbook.

**CSV format** (`storage/app/pakur-logbook.csv`):
```
rake_number,placed_at,released_at,source
95,2026-04-01 08:00:00,2026-04-01 16:00:00,logbook
```

**Run:**
```bash
php artisan pakur:backfill-placement --file=storage/app/pakur-logbook.csv
# Add --force to overwrite rakes that already have placement_time set
```

Skips rows where `rake_number` doesn't match. Without `--force`, preserves any pre-existing values. Outputs `Updated N rake(s). Skipped M row(s).`
