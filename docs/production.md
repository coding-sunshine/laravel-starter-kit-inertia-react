# Production Release — feature/laravel-13

Steps to apply when merging `feature/laravel-13` → `railway` and releasing to production.

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

Five new migrations ship in this branch. `migrate --force` above runs them, but listed here for awareness:

| Migration | What it does |
|-----------|-------------|
| `2026_04_29_142818_create_loading_overrides_table` | PCC loading overrides log |
| `2026_04_29_220306_create_loadrite_settings_table` | Stores encrypted Loadrite API tokens per siding |
| `2026_04_29_220534_add_loadrite_columns_to_wagon_loading_table` | `loadrite_weight_mt`, `weight_source`, `loadrite_last_synced_at`, `loadrite_override` on `wagon_loading` |
| `2026_04_30_000001_add_migration_columns_to_penalties_table` | `migrated_at`, `migration_note` on `penalties` |
| `2026_04_30_055618_add_site_name_to_loadrite_settings` | `site_name` column (required for API calls) |

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

Three new queues were added for Loadrite polling. Horizon must be restarted to pick them up:

```bash
php artisan horizon:terminate
# Let your supervisor/process manager restart Horizon automatically
```

Verify the new queues appear in the Horizon dashboard (`/horizon`):
- `loadrite-poll`
- `loadrite-sync`
- `loadrite-alerts`

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

## 9. New page — siding monitor

A new route is live at `/sidings/{siding}/monitor`. No permissions gate is on it — any authenticated user who knows the URL can access it. If you want to restrict it, add middleware or a gate before go-live.

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
```

---

## Rollback

If anything goes wrong:

```bash
# Roll back the 5 new migrations in reverse order
php artisan migrate:rollback --step=5

# Clear all caches
php artisan optimize:clear
```

> The `loadrite_settings` table rows are not touched by rollback — delete manually if needed:
> ```sql
> DELETE FROM loadrite_settings WHERE siding_id = 2;
> ```
