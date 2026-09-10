# Functional Testing — AI + Penalty Saving (CLI / tinker)

For testing actual behavior of penalty calculation, reconciliation, and AI actions on `feature/laravel-13`. **No UI required** — runs entirely via `php artisan tinker` and `php artisan` commands.

Run from project root: `cd /Users/apple/Code/clients/shar/railwayrack`.

---

## 0. Prereqs (one-time)

```bash
php artisan migrate
php artisan db:seed --class='Database\Seeders\Essential\CommodityUtilisationThresholdSeeder'
php artisan db:seed --class='Database\Seeders\Essential\PenaltyTypesSeeder'
php artisan optimize:clear
```

For AI actions, set the LLM provider in `.env`:
```env
PRISM_PROVIDER=anthropic        # or openai, gemini
ANTHROPIC_API_KEY=sk-ant-...    # whichever provider key
```
Without keys → AI actions throw or return empty. Penalty calculation actions work without AI.

---

## 1. Demurrage formula — POST penalty-fix

`ApplyDemurragePenaltyAction` uses `placement_time → loading_end_time`, free-time from `section_timers.loading.free_minutes`, progressive multiplier (≤6h=1×, 6–12h=2×, 12–24h=3×, 24–48h=4×, >48h=6×), `₹225 × wagon_count × hours × multiplier`.

```bash
php artisan tinker --execute '
use App\Actions\ApplyDemurragePenaltyAction;
use App\Models\Rake;

// Pick a rake with placement+loading_end set; 7149 (Pakur) is empty.
// Use a Dumka rake instead:
$rake = Rake::query()
    ->whereNotNull("placement_time")
    ->whereNotNull("loading_end_time")
    ->where("siding_id", 2)
    ->first();

if (!$rake) { echo "No suitable rake found\n"; exit; }

echo "Rake: " . $rake->rake_number . " (id=" . $rake->id . ")\n";
echo "Placement: " . $rake->placement_time . "\n";
echo "Loading end: " . $rake->loading_end_time . "\n";

$result = resolve(ApplyDemurragePenaltyAction::class)->handle($rake);
print_r($result);
'
```

**Expected:** result array with `applied`, `chargedHours`, `excessMinutes`, `totalMinutes`, `freeMinutes`, `baseRate`, `rateMultiplier`, `amount`. If the rake was within free time, `applied=false` and `amount=0`.

**Verify it persisted:**
```bash
php artisan tinker --execute '
use App\Models\AppliedPenalty;
$row = AppliedPenalty::query()
    ->whereJsonContains("meta->source", "demurrage")
    ->whereHas("penaltyType", fn($q) => $q->where("code", "DEM"))
    ->latest()
    ->first();
print_r($row?->toArray());
print_r($row?->meta);
'
```

**Expected:** row with `meta.formula = "demurrage_hours × weight_mt × rate_per_mt_hour"` (legacy formula breakdown), `meta.excess_hours`, `meta.wagon_count`, `meta.base_rate`, `meta.rate_multiplier`. Also confirms `AppliedPenaltyPersisted` event fired (kicks off reconciliation downstream).

---

## 2. PLO calculator (Stage 1, new)

Compute-only, returns DTO; no DB writes:

```bash
php artisan tinker --execute '
use App\Actions\CalculatePloPenaltyAction;
use App\Models\Rake;

$rake = Rake::query()
    ->whereNotNull("commodity_grade")
    ->whereHas("rakeWeighments")
    ->first()
    ?? Rake::query()->whereHas("rakeWeighments")->first();

if (!$rake) { echo "No rake with weighments found\n"; exit; }

$weighment = $rake->rakeWeighments()->first();
if (!$weighment) { echo "No weighment\n"; exit; }

$result = resolve(CalculatePloPenaltyAction::class)->handle($rake, $weighment);
echo "Rake: " . $rake->rake_number . " grade=" . ($rake->commodity_grade ?? "null") . "\n";
print_r((array) $result);
'
```

**Expected:** DTO with `rakeId`, `chargeableWeightMt`, `totalLoadedWeightMt`, `shortfallMt`, `rate`, `amount`, `isApplicable()`. Falls back to 0.95 utilisation if no `commodity_utilisation_thresholds` row matches.

**Persist + emit event:**
```bash
php artisan tinker --execute '
use App\Actions\ApplyPloPenaltyAction;
use App\Models\Rake;
$rake = Rake::query()->whereHas("rakeWeighments")->first();
$weighment = $rake->rakeWeighments()->first();
$applied = resolve(ApplyPloPenaltyAction::class)->handle($rake, $weighment);
echo $applied ? "PLO applied: ₹" . $applied->amount . "\n" : "No PLO (no shortfall)\n";
'
```

---

## 3. Weighment penalties — POL1 / POLA

Already in production. To exercise:

```bash
php artisan tinker --execute '
use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Models\Rake;
$rake = Rake::query()->whereHas("rakeWeighments")->first();
$weighment = $rake->rakeWeighments()->first();
resolve(ApplyWeighmentPenaltiesAction::class)->handle($rake, $weighment);
echo "Done\n";
print_r(\App\Models\AppliedPenalty::query()->where("rake_id", $rake->id)->whereHas("penaltyType", fn($q)=>$q->whereIn("code",["POL1","POLA"]))->get()->map->only(["id","rate","amount","meta"])->toArray());
'
```

**Expected:** rows with `meta.source = "weighment"`. POL1 = per-wagon overload, POLA = aggregate overload.

---

## 4. Reconciliation — predicted vs billed (Stage 1, new)

Direct invocation:

```bash
php artisan tinker --execute '
use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\Rake;

$rake = Rake::find(7149); // Pakur rake with billed RR snapshots
$outcome = resolve(ReconcilePenaltyHeadsAction::class)->handle($rake);
echo "Rake " . $rake->id . " — created=" . count($outcome->createdIds) .
     ", updated=" . count($outcome->updatedIds) .
     ", dispute candidates=" . count($outcome->disputeCandidateIds) . "\n";

print_r(\App\Models\PenaltyReconciliation::query()
    ->where("rake_id", $rake->id)
    ->get()
    ->map->only(["penalty_code","predicted_amount","billed_amount","variance","variance_pct","dispute_candidate"])
    ->toArray());
'
```

**Expected:** rake 7149 has 2 RR snapshots (DEM ₹8850, PLO ₹5000). Both written to `penalty_reconciliations` with `dispute_candidate=true` (no predicted side, billed > 0).

**Test idempotency:**
```bash
php artisan tinker --execute '
use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;

$rake = Rake::find(7149);
$action = resolve(ReconcilePenaltyHeadsAction::class);

$action->handle($rake);
$action->handle($rake);
$action->handle($rake);

echo "Rows for rake 7149: " . PenaltyReconciliation::query()->where("rake_id", $rake->id)->count() . " (expect: 2)\n";
'
```

**Test the queue path (Job + Listener):**
```bash
# Confirm event listeners are registered
php artisan event:list | grep -E "AppliedPenaltyPersisted|RrPenaltySnapshotsImported"
# Expected: ReconcileOnAppliedPenalty + ReconcileOnRrImport listed

# Dispatch the job synchronously (no worker needed if QUEUE_CONNECTION=sync)
php artisan tinker --execute '
use App\Jobs\ReconcilePenaltyHeadsJob;
use App\Models\Rake;
ReconcilePenaltyHeadsJob::dispatchSync(Rake::find(7149));
echo "Job done\n";
'

# If using a real queue, watch logs:
php artisan queue:work --queue=penalties --once -v
```

**Test event emission triggers reconciliation:**
```bash
php artisan tinker --execute '
use App\Events\AppliedPenaltyPersisted;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;

$rake = Rake::find(7149);
PenaltyReconciliation::query()->where("rake_id", $rake->id)->delete();
echo "Cleared. Count: " . PenaltyReconciliation::query()->where("rake_id", $rake->id)->count() . "\n";

AppliedPenaltyPersisted::dispatch($rake, "manual-test");
sleep(1);
echo "After event: " . PenaltyReconciliation::query()->where("rake_id", $rake->id)->count() . "\n";
'
# If sync queue: count goes to 2 immediately. If real queue: run a worker first.
```

---

## 5. Retroactive recalc — `penalties:recalculate`

Tests that the demurrage-fix migrates legacy bad amounts:

```bash
# Dry run — no DB writes, prints diff
php artisan penalties:recalculate --dry-run | head -30

# Single rake test
php artisan penalties:recalculate --rake=7149 --dry-run

# Apply (after reviewing dry-run)
php artisan penalties:recalculate --rake=7149
```

**Expected:** dry-run outputs CSV-style rows: `rake_id, rake_number, siding, old_amount, new_amount, delta`. Apply writes `meta.recalculated_at = <now>` and `meta.correction_reason = "formula_fix_2026-04-29"`.

---

## 6. AI Actions — sanity check (require LLM API key)

### 6.1 Root-cause classifier

```bash
php artisan tinker --execute '
use App\Actions\ClassifyPenaltyRootCauseAction;
use App\Models\Penalty;

$p = Penalty::query()->first();
if (!$p) { echo "No Penalty rows in legacy table\n"; exit; }

$p->update([
    "root_cause" => "Loader breakdown caused 4-hour delay",
    "description" => "Hydraulic failure on Loader 2 stopped pour at 60% complete"
]);

resolve(ClassifyPenaltyRootCauseAction::class)->handle($p);
$p->refresh();
echo "category: " . $p->root_cause_category . "\n";
echo "preventable: " . ($p->is_preventable ? "yes" : "no") . "\n";
echo "remediation: " . $p->suggested_remediation . "\n";
'
```

**Expected:** AI fills `root_cause_category` (one of `equipment_failure`, `labour`, `scheduling`, `weather`, `process`, `other`), `is_preventable` boolean, and a remediation suggestion.

### 6.2 Penalty insights — 5 cost-saving recommendations

```bash
php artisan tinker --execute '
use App\Actions\GeneratePenaltyInsightsAction;
$siding_ids = [1, 2, 3];
$insights = resolve(GeneratePenaltyInsightsAction::class)->handle($siding_ids);
foreach ($insights["insights"] ?? [] as $i => $it) {
    echo ($i+1) . ". [" . $it["severity"] . "] " . $it["title"] . " — ₹" . ($it["estimated_savings_inr"] ?? 0) . "\n";
}
'
```

**Expected:** 5 insights with `severity` (high/medium/low), `title` (under 60 chars), `description`, `estimated_savings_inr`, `category`. If no penalty data → insights array empty.

### 6.3 Penalty predictions

```bash
php artisan tinker --execute '
use App\Actions\GeneratePenaltyPredictionsAction;
$preds = resolve(GeneratePenaltyPredictionsAction::class)->handle([1, 2, 3]);
print_r($preds);
'
```

### 6.4 Dispute recommendation

```bash
php artisan tinker --execute '
use App\Actions\RecommendDisputeAction;
use App\Models\Penalty;
$penalty = Penalty::query()->first();
if (!$penalty) { echo "No penalties\n"; exit; }
$rec = resolve(RecommendDisputeAction::class)->handle($penalty);
print_r($rec);
'
```

**Expected:** structured object with `should_dispute`, `confidence`, `reasoning`, `suggested_grounds`.

### 6.5 Daily briefing

```bash
php artisan tinker --execute '
use App\Actions\GenerateDailyBriefingAction;
echo resolve(GenerateDailyBriefingAction::class)->handle()["briefing"] ?? "empty";
'
```

### 6.6 Loading recommendation

```bash
php artisan tinker --execute '
use App\Actions\GenerateLoadingRecommendationAction;
use App\Models\Rake;
$rake = Rake::query()->whereHas("wagons")->first();
if (!$rake) { echo "No rake\n"; exit; }
$rec = resolve(GenerateLoadingRecommendationAction::class)->handle($rake);
print_r($rec);
'
```

---

## 7. Scheduled jobs — exercise once manually

```bash
# Demurrage threshold checker (runs every 5 min via scheduler)
php artisan rrmcs:check-demurrage

# Weekly insights generator (Mondays 06:00)
php artisan rrmcs:generate-penalty-insights

# Weekly admin email (Mondays 08:00)
php artisan rrmcs:send-weekly-penalty-report
```

For the email one, check `storage/logs/laravel.log` and the queue worker (`queue:work` running) to confirm it dispatched.

---

## 8. End-to-end happy path

Simulates: weighment finalises → POL1/POLA + PLO applied → AppliedPenaltyPersisted fires → reconciliation runs against billed snapshots.

```bash
php artisan tinker --execute '
use App\Actions\ApplyPloPenaltyAction;
use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;

$rake = Rake::query()->whereHas("rakeWeighments")->first();
if (!$rake) { echo "No rake with weighments\n"; exit; }
$w = $rake->rakeWeighments()->first();

echo "BEFORE — applied: " . AppliedPenalty::query()->where("rake_id",$rake->id)->count() .
     ", reconciliations: " . PenaltyReconciliation::query()->where("rake_id",$rake->id)->count() . "\n";

resolve(ApplyWeighmentPenaltiesAction::class)->handle($rake, $w);
resolve(ApplyPloPenaltyAction::class)->handle($rake, $w);
sleep(1); // wait for sync queue listeners

echo "AFTER — applied: " . AppliedPenalty::query()->where("rake_id",$rake->id)->count() .
     ", reconciliations: " . PenaltyReconciliation::query()->where("rake_id",$rake->id)->count() . "\n";

print_r(PenaltyReconciliation::query()
    ->where("rake_id",$rake->id)
    ->get()
    ->map->only(["penalty_code","predicted_amount","billed_amount","variance","dispute_candidate"])
    ->toArray());
'
```

**Expected:** applied count rises, reconciliation count rises, output shows per-head reconciliation rows.

---

## 9. Data hygiene — check unused features

```bash
# Penalty types loaded
php artisan tinker --execute 'echo App\Models\PenaltyType::count() . " types — " . App\Models\PenaltyType::pluck("code")->join(", ") . PHP_EOL;'
# Expected: 8 codes — POL1, POLA, PLO, ENHC, WMC, ULC, SPL, MCF

# Commodity thresholds seeded
php artisan tinker --execute 'echo App\Models\CommodityUtilisationThreshold::count() . " thresholds" . PHP_EOL;'
# Expected: 6

# Reconciliation table accessible
php artisan tinker --execute 'echo App\Models\PenaltyReconciliation::count() . " reconciliations" . PHP_EOL;'

# Listener auto-discovery
php artisan event:list | grep -E "Reconcile|AppliedPenalty|RrPenaltySnapshots"
```

---

## 10. Failure scenarios — confirm graceful degradation

| Scenario | Command | Expected |
|---|---|---|
| Rake missing placement_time | `resolve(ApplyDemurragePenaltyAction::class)->handle(Rake::find(7149));` | returns null, no exception |
| No PLO threshold for grade | Set `$rake->commodity_grade = 'XYZ_NEW'` then call calc | falls back to 0.95, no exception |
| AI key missing | unset `ANTHROPIC_API_KEY` then run insights | logs warning, action returns gracefully |
| Reconcile rake with no data | `dispatchSync(Rake::factory()->create())` | no rows written, no exception |

---

## What you do NOT need to test

- UI rendering (covered by `docs/e2e-testing-guide.md`)
- Filament resource forms (admin only — already verified working)
- Wayfinder type generation (build-time, not runtime)
- Inertia v3 prop hydration (covered by build pass)

## If anything fails

1. Check `storage/logs/laravel.log` tail.
2. Run `php artisan optimize:clear` then retry.
3. Confirm `composer install` ran (the Machour patch must be applied).
4. Confirm `php artisan migrate:status` shows all 10 new migrations as `Ran`.
