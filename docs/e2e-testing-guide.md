# E2E Testing Guide — feature/laravel-13 release

Manual QA checklist for the `feature/laravel-13` → `railway` release. One pass through this confirms every user-visible change.

**Test environment:** staging URL (or `https://rrmanagementlatest.test/` locally)

---

## Prerequisites

Before starting, on the test server:

```bash
php artisan migrate --force
php artisan db:seed --class='Database\Seeders\Essential\CommodityUtilisationThresholdSeeder' --force
npm run build
php artisan optimize:clear
php artisan horizon:terminate   # supervisor restarts it
php artisan reverb:start --port=8080 &   # if not already running
```

You will need:
- 1 admin account (`super_admin` role)
- 1 siding-attached user with `siding_in_charge` role on **Pakur Siding** (siding_id = 1)
- 1 siding-attached user with `siding_in_charge` role on **Dumka Siding** (siding_id = 2)
- A test rake at Pakur with no `placement_time` (rake_id 7149 is suitable per current data)
- A test rake at Dumka with completed weighment data

---

## Test matrix

Mark each `[ ] PASS`, `[x] FAIL — <reason>` as you go.

### A. Demurrage formula fix

| # | Step | Expected |
|---|---|---|
| A1 | Run `php artisan penalties:recalculate --dry-run` | CSV-style output, no DB writes, lists rake_id, old_amount, new_amount, delta |
| A2 | Pick a rake with placement_time + loading_end_time set, ≥ 6h apart | `--dry-run` reports new_amount = `ceil(excess_hours) × wagon_count × multiplier × 225` |
| A3 | Visit `/admin` → Penalties section, open one demurrage row | `meta` contains `formula`, `excess_hours`, `wagon_count`, `base_rate=225`, `rate_multiplier` |
| A4 | Run `php artisan penalties:recalculate` (no flag) | Writes updates; `recalculated_at` set in `meta` |

### B. Stage 1 — penalty reconciliation

| # | Step | Expected |
|---|---|---|
| B1 | `php artisan tinker --execute 'App\Jobs\ReconcilePenaltyHeadsJob::dispatchSync(App\Models\Rake::find(7149));'` | Exits without error |
| B2 | Visit `/admin/penalty-reconciliations` | Table shows 2 rows for rake 7149 (DEM, PLO). Both have `Dispute? = Yes` (red flag icon). Predicted column blank/zero, Billed column shows ₹8850 + ₹5000. |
| B3 | Filter table by `Penalty code = DEM` | Only DEM rows visible |
| B4 | Filter by `Dispute candidate = Yes` | Only flagged rows visible |
| B5 | Visit any rake show page (`/rakes/{id}`) where reconciliations exist | "Penalty reconciliation" card visible after KPI grid, before workflow steps |
| B6 | On the rake show card, hover over a variance row | Row background highlights; variance value colored red (over) or green (under) |
| B7 | On the rake show card, find a row with dispute flag | Shows red `Flag` icon + "Yes" label; non-flagged shows em dash |

### C. Stage 1 — PLO calculator

| # | Step | Expected |
|---|---|---|
| C1 | Visit `/admin/commodity-utilisation-thresholds` | 6 seeded rows (G1–G5 + UNGRADED, all 0.95) |
| C2 | Click "Create" → fill `commodity_grade=TEST`, `utilisation_threshold=0.92`, `effective_from=now`, `source=qa` | Saved; appears in list, sorted by `effective_from desc` |
| C3 | Edit it, change `utilisation_threshold=0.93`, save | Updated value persists on reload |
| C4 | Delete the TEST row | Removed; list shows 6 again |
| C5 | Upload a weighment PDF for a Dumka rake (existing flow under rake show) | After processing, an `applied_penalty` row exists with `meta.source='plo'` if total loaded < chargeable; visible on rake show "Predicted ₹" KPI |

### D. Stage 1 — Pakur quick-placement

| # | Step | Expected |
|---|---|---|
| D1 | Log in as Pakur siding_in_charge user | Dashboard loads |
| D2 | Visit `/sidings/1/quick-placement` | Header shows "Pakur Siding". Counts pill shows total + per-status. List of up to 50 rakes. |
| D3 | Pick a rake with status "Not placed" → tap **Placed** | Button changes to "Saving…"; on completion status badge becomes "Loading"; "Placed Xs ago" appears under rake number |
| D4 | On same rake, tap **Released** | Button changes to "Saving…"; status becomes "Released"; both timestamps shown |
| D5 | Try to tap **Released** before **Placed** on a fresh rake | Released button is disabled |
| D6 | Try to tap **Placed** twice | Second tap disabled (status now "Loading") |
| D7 | Visit on a phone-sized viewport (375 px) | Layout single-column, buttons full width, no horizontal scroll |
| D8 | Log in as Dumka user → visit `/sidings/1/quick-placement` (Pakur) | 403 Forbidden |
| D9 | Run `php artisan pakur:backfill-placement --file=storage/app/test.csv` with a CSV row matching an existing rake_number | "Updated 1 rake(s). Skipped 0 row(s)." Rake's placement_time + loading_end_time updated |
| D10 | Re-run the same CSV without `--force` against a rake whose placement_time is already set | "Updated 0 rake(s). Skipped 1 row(s)." Existing values preserved |

### E. Loadrite integration

| # | Step | Expected |
|---|---|---|
| E1 | `php artisan loadrite:store-token --siding=2` then enter prompts | Token saved encrypted; `loadrite_settings` row exists for siding 2 |
| E2 | `php artisan loadrite:start-polling` | Dispatches `PollLoadriteJob` for siding 2 |
| E3 | Open Horizon dashboard `/horizon` | `loadrite-poll`, `loadrite-sync`, `loadrite-alerts`, **`penalties`**, `default` queues all listed and consuming |
| E4 | Wait 60 seconds, then run `php artisan tinker --execute 'echo App\Models\WagonLoading::whereNotNull("loadrite_weight_mt")->count() . PHP_EOL;'` | Count > 0 (assuming Loadrite scale was active) |
| E5 | Visit `/sidings/2/monitor` | Real-time WagonTrain visualisation; CountdownTimer updates; AlertsFeed populated |
| E6 | Cause an overload on a wagon (set net_weight to 110% of cc) | Reverb broadcast fires; toast notification appears on dashboard for active users; `notifications` table row created |

### F. Dashboard redesign

| # | Step | Expected |
|---|---|---|
| F1 | Log in as super_admin → visit `/admin` (Filament) | Filament v5.6 admin loads, no broken pages |
| F2 | Visit `/dashboard` | New dark navy header with pill nav (5 sections: Overview, Operations, Performance, PenaltyControl, LoaderOverloading, PowerPlant) |
| F3 | Click each pill | Section content swaps inline; URL hash updates; active pill has high-contrast styling |
| F4 | Resize to 1024 px | Pill nav remains usable; cards reflow to 2-up |
| F5 | Resize to 375 px | Pill nav scrolls horizontally; cards stack 1-up; no layout overflow |

### G. Critical regressions to confirm did NOT break

| # | Step | Expected |
|---|---|---|
| G1 | Existing rake creation flow (`/rakes/create`) | Creates rake, no errors |
| G2 | Existing weighment PDF upload | Imports successfully; predicted penalties applied (POL1/POLA + PLO if shortfall) |
| G3 | Existing RR document upload | Imports successfully; `rr_penalty_snapshots` rows written; reconciliation runs (`ReconcilePenaltyHeadsJob` visible in Horizon) |
| G4 | Existing user management, role assignment, organization switcher | All work as before |
| G5 | Existing `/help` and contact submission flow | Unaffected |
| G6 | Email send (any transactional) | Goes through `martinpetricko/laravel-database-mail` if templates present, otherwise standard SMTP |

---

## Smoke commands (run after deploy, before opening to users)

```bash
# 1. Migrations
php artisan migrate:status | tail -15
# Expected: last 10 entries show `Yes` in the Ran column

# 2. PLO thresholds seeded
php artisan tinker --execute 'echo App\Models\CommodityUtilisationThreshold::count() . PHP_EOL;'
# Expected: 6 (or 7 if you ran the C2 test)

# 3. Reconciliation listeners auto-discovered
php artisan event:list | grep -E 'AppliedPenaltyPersisted|RrPenaltySnapshotsImported'
# Expected: both events listed with their listener mappings

# 4. Horizon queues
php artisan horizon:status
# Expected: status: running

# 5. Build assets present
ls -la public/build/assets/ | wc -l
# Expected: >50 files (CSS + JS chunks)

# 6. Reverb up
curl -s http://localhost:8080/app/...   # adjust to your Reverb endpoint
# Expected: 200 or upgrade-required (depending on auth)
```

---

## Failure escalation

| Failed test | First action |
|---|---|
| A1–A4 | Inspect `app/Actions/ApplyDemurragePenaltyAction.php` formula. Check `PenaltyType['DEM'].default_rate` is 225. |
| B1–B7 | Check `event:list` shows listeners. Check `penalties` queue has workers in Horizon. Verify `Mattiverse\Userstamps\Traits\Userstamps` trait resolves. |
| C1–C5 | Verify seeder ran. Check `commodity_grade` column exists on `rakes`. Check PLO PenaltyType row has `default_rate=100`. |
| D1–D10 | Check user-siding pivot (`user_siding`). Confirm `siding_in_charge` role exists. CSV header line must be `rake_number,placed_at,released_at,source`. |
| E1–E6 | Check `loadrite_settings` row exists with non-empty `access_token`. Check Horizon supervisor processes Loadrite queues. |
| F1–F5 | Re-run `npm run build`. Check `optimize:clear` was run after deploy. |
| G1–G6 | Inspect Laravel log (`storage/logs/laravel.log`). Most regressions surface as 500 with stacktrace. |

---

**Time estimate for full pass:** 60–90 minutes for a single tester. Sections A, F, G can run in parallel with sections B, C, D, E.

**Sign-off:** Tester records pass/fail per row, dates, and signs at bottom of a printed copy or shared sheet before merge to `railway`.
