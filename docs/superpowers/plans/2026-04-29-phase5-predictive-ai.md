# Phase 5: Predictive AI & Optimization — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Surface existing AI predictions and add pre-loading Gemma 4 recommendations to prevent PCC violations before they become penalties.

**Architecture:** Additive only — new Prism action, new Inertia deferred props, new dashboard widgets, new event + scheduled command for weekly report. No existing endpoints modified.

**Tech Stack:** Laravel 13, Pest, Prism (OpenRouter/Gemma 4), Inertia v3, React 19, Tailwind v4

---

## Task 1: `GenerateLoadingRecommendationAction` — New Prism Action

Follow `GeneratePenaltyInsightsAction` exactly: constructor injects `PrismService`, `handle()` checks availability, uses `Cache::has/put`, calls `$this->prism->structured(...)→withPrompt(...)→withSchema(...)→asStructured()`.

### Steps

- [ ] **1.1** Create the action:
  ```bash
  php artisan make:action "GenerateLoadingRecommendationAction" --no-interaction
  ```

- [ ] **1.2** Write `app/Actions/GenerateLoadingRecommendationAction.php`:

  ```php
  <?php

  declare(strict_types=1);

  namespace App\Actions;

  use App\Models\Rake;
  use App\Services\PrismService;
  use Illuminate\Support\Facades\Cache;
  use Illuminate\Support\Facades\DB;
  use Prism\Prism\Schema\ObjectSchema;
  use Prism\Prism\Schema\StringSchema;
  use Throwable;

  final readonly class GenerateLoadingRecommendationAction
  {
      public function __construct(private PrismService $prism) {}

      /**
       * Generate a loading recommendation for a rake before loading begins.
       * Cached for 6 hours keyed on rake_id + siding_id.
       */
      public function handle(Rake $rake): ?string
      {
          $sidingId = (int) $rake->siding_id;
          $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$sidingId}";

          if (Cache::has($cacheKey)) {
              $cached = Cache::get($cacheKey);

              return $cached === '__unavailable__' ? null : $cached;
          }

          if (! $this->prism->isAvailable()) {
              Cache::put($cacheKey, '__unavailable__', 21600);

              return null;
          }

          $data = $this->aggregateData($rake, $sidingId);
          $prompt = $this->buildPrompt($rake, $data);

          try {
              $response = $this->prism->structured($this->prism->fastModel())
                  ->withPrompt($prompt)
                  ->withSchema($this->buildSchema())
                  ->asStructured();

              /** @var array{recommendation: string} $structured */
              $structured = $response->structured;

              $recommendation = trim($structured['recommendation'] ?? '');

              if ($recommendation === '') {
                  Cache::put($cacheKey, '__unavailable__', 3600);

                  return null;
              }

              Cache::put($cacheKey, $recommendation, 21600);

              return $recommendation;
          } catch (Throwable) {
              Cache::put($cacheKey, '__unavailable__', 3600);

              return null;
          }
      }

      private function buildSchema(): ObjectSchema
      {
          return new ObjectSchema(
              name: 'loading_recommendation',
              description: 'AI loading recommendation to prevent PCC violations',
              properties: [
                  new StringSchema(
                      name: 'recommendation',
                      description: 'Plain-text recommendation under 200 words. Suggest target load per wagon type in MT to stay under PCC. Be specific with numbers.',
                  ),
              ],
              requiredFields: ['recommendation'],
          );
      }

      /**
       * @return array<string, mixed>
       */
      private function aggregateData(Rake $rake, int $sidingId): array
      {
          // Average loaded MT per wagon_type for this siding over last 30 days
          $avgByType = DB::select(
              <<<'SQL'
              SELECT
                  w.wagon_type,
                  COUNT(wl.id)                              AS loading_count,
                  ROUND(AVG(wl.loaded_quantity_mt)::numeric, 2) AS avg_loaded_mt,
                  ROUND(AVG(w.pcc_weight_mt)::numeric, 2)  AS avg_pcc_mt,
                  SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt THEN 1 ELSE 0 END) AS overload_count
              FROM wagon_loading wl
              JOIN wagons w ON w.id = wl.wagon_id
              JOIN rakes r  ON r.id = wl.rake_id
              WHERE r.siding_id = ?
                AND r.deleted_at IS NULL
                AND wl.created_at >= NOW() - INTERVAL '30 days'
                AND w.wagon_type IS NOT NULL
              GROUP BY w.wagon_type
              ORDER BY overload_count DESC
              SQL,
              [$sidingId],
          );

          // Wagon type distribution in this rake
          $rakeWagonTypes = $rake->wagons
              ->whereNull('is_unfit')
              ->groupBy('wagon_type')
              ->map(fn ($wagons, $type): array => [
                  'wagon_type' => $type,
                  'count' => $wagons->count(),
                  'pcc_weight_mt' => $wagons->first()?->pcc_weight_mt,
              ])
              ->values()
              ->all();

          return [
              'siding_avg_by_wagon_type' => $avgByType,
              'rake_wagon_composition' => $rakeWagonTypes,
              'rake_total_wagons' => $rake->wagons->count(),
              'siding_name' => $rake->siding?->name ?? 'Unknown',
          ];
      }

      /**
       * @param  array<string, mixed>  $data
       */
      private function buildPrompt(Rake $rake, array $data): string
      {
          $json = json_encode($data, JSON_PRETTY_PRINT);

          return <<<PROMPT
          You are RRMCS AI, a loading optimization assistant for railway coal operations at BGR Mining.

          A rake is about to start loading at siding "{$data['siding_name']}". The rake has {$data['rake_total_wagons']} wagons.

          Based on the last 30 days of wagon loading data for this siding, provide a concise recommendation (max 200 words) on:
          1. Target load per wagon type (in MT) to stay under PCC limits
          2. Which wagon types historically get overloaded and by how much
          3. One specific tip to reduce PCC violations

          Historical loading data:
          {$json}

          Be specific with numbers. Mention wagon types by name. Keep it practical and actionable.
          PROMPT;
      }
  }
  ```

- [ ] **1.3** Run Pint:
  ```bash
  vendor/bin/pint app/Actions/GenerateLoadingRecommendationAction.php --format agent
  ```

- [ ] **1.4** Commit:
  ```bash
  git add app/Actions/GenerateLoadingRecommendationAction.php
  git commit -m "feat: add GenerateLoadingRecommendationAction with Prism/Gemma 4 integration"
  ```

---

## Task 2: Wire Loading Recommendation into `RakeLoaderController::loading()` + UI Card

### Steps

- [ ] **2.1** Edit `app/Http/Controllers/Rakes/RakeLoaderController.php` — update the `loading()` method to add a deferred prop:

  ```php
  // Add to top imports:
  use App\Actions\GenerateLoadingRecommendationAction;

  // Replace the Inertia::render call in loading() with:
  return Inertia::render('rake-loader/loading', [
      'rake' => self::buildRakeLoaderRakePayload($rake),
      'loadingRecommendation' => Inertia::defer(
          fn () => app(GenerateLoadingRecommendationAction::class)->handle($rake),
      ),
  ]);
  ```

- [ ] **2.2** Run Pint:
  ```bash
  vendor/bin/pint app/Http/Controllers/Rakes/RakeLoaderController.php --format agent
  ```

- [ ] **2.3** Edit `resources/js/pages/rake-loader/loading.tsx` — add the deferred prop and card UI.

  **Add to the Props interface:**
  ```tsx
  interface Props {
      rake: RakeHydrated;
      loadingRecommendation?: string | null;
  }
  ```

  **Update component signature:**
  ```tsx
  export default function RakeLoaderLoading({ rake: initialRake, loadingRecommendation }: Props) {
  ```

  **Add the AI recommendation card component** (place above the PCC status pills section, after `<Head>` / breadcrumb block, before `<PccStatusPills ...>`):

  ```tsx
  {/* AI Loading Recommendation */}
  {loadingRecommendation === undefined ? (
      // Deferred prop still loading — skeleton
      <div className="mb-4 animate-pulse rounded-lg border-l-4 border-[#2d6a4f] bg-white p-4 shadow-sm">
          <div className="mb-2 h-4 w-48 rounded bg-gray-200" />
          <div className="space-y-2">
              <div className="h-3 w-full rounded bg-gray-200" />
              <div className="h-3 w-5/6 rounded bg-gray-200" />
              <div className="h-3 w-4/6 rounded bg-gray-200" />
          </div>
      </div>
  ) : loadingRecommendation ? (
      <div className="mb-4 rounded-lg border-l-4 border-[#2d6a4f] bg-white p-4 shadow-sm">
          <div className="mb-2 flex items-center gap-2">
              <span className="text-base">💡</span>
              <span className="text-sm font-semibold text-[#2d6a4f]">AI Recommendation</span>
          </div>
          <p className="text-sm leading-relaxed text-gray-700">{loadingRecommendation}</p>
      </div>
  ) : null}
  ```

- [ ] **2.4** Build frontend and verify:
  ```bash
  npm run build 2>&1 | grep -E "error|✓ built"
  ```

- [ ] **2.5** Commit:
  ```bash
  git add app/Http/Controllers/Rakes/RakeLoaderController.php resources/js/pages/rake-loader/loading.tsx
  git commit -m "feat: add deferred AI loading recommendation card to rake loader"
  ```

---

## Task 3: PenaltyPrediction Dashboard Widget — Backend Prop + Frontend Component

### Steps

- [ ] **3.1** Edit `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`.

  **Add import at top:**
  ```php
  use App\Models\PenaltyPrediction;
  ```

  **Add a new private method:**
  ```php
  /**
   * @param  array<int>  $sidingIds
   * @return list<array{siding_name: string, risk_level: string, predicted_amount_min: float, predicted_amount_max: float, top_recommendation: string|null}>
   */
  private function buildPenaltyPredictions(array $sidingIds): array
  {
      if ($sidingIds === []) {
          return [];
      }

      return PenaltyPrediction::query()
          ->with('siding:id,name')
          ->whereIn('siding_id', $sidingIds)
          ->where('prediction_date', '>=', now()->toDateString())
          ->orderByRaw("CASE risk_level WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
          ->limit(5)
          ->get()
          ->map(fn (PenaltyPrediction $p): array => [
              'siding_name' => $p->siding?->name ?? 'Unknown',
              'risk_level' => $p->risk_level,
              'predicted_amount_min' => (float) $p->predicted_amount_min,
              'predicted_amount_max' => (float) $p->predicted_amount_max,
              'top_recommendation' => isset($p->recommendations[0]) ? (string) $p->recommendations[0] : null,
          ])
          ->all();
  }
  ```

  **In `__invoke()`, add prop inside `Inertia::render()` call** (append after existing props):
  ```php
  'penaltyPredictions' => $this->buildPenaltyPredictions($resolved['filteredSidingIds']),
  ```

- [ ] **3.2** Run Pint:
  ```bash
  vendor/bin/pint app/Http/Controllers/Dashboard/ExecutiveDashboardController.php --format agent
  ```

- [ ] **3.3** Create `resources/js/components/dashboard/penalty-predictions-widget.tsx`:

  ```tsx
  import { Badge } from '@/components/ui/badge';
  import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

  type PenaltyPrediction = {
      siding_name: string;
      risk_level: 'high' | 'medium' | 'low';
      predicted_amount_min: number;
      predicted_amount_max: number;
      top_recommendation: string | null;
  };

  interface Props {
      predictions: PenaltyPrediction[];
  }

  const riskConfig = {
      high: { label: 'High Risk', className: 'bg-red-100 text-red-700 border-red-200' },
      medium: { label: 'Medium', className: 'bg-amber-100 text-amber-700 border-amber-200' },
      low: { label: 'Low', className: 'bg-green-100 text-green-700 border-green-200' },
  };

  function formatInr(amount: number): string {
      return new Intl.NumberFormat('en-IN', {
          style: 'currency',
          currency: 'INR',
          maximumFractionDigits: 0,
      }).format(amount);
  }

  export function PenaltyPredictionsWidget({ predictions }: Props) {
      if (predictions.length === 0) {
          return null;
      }

      return (
          <Card>
              <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-sm font-semibold text-gray-900">
                      <span>🔮</span>
                      <span>Penalty Forecast — Next 7 Days</span>
                  </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                  {predictions.map((p, i) => {
                      const config = riskConfig[p.risk_level] ?? riskConfig.low;
                      return (
                          <div key={i} className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                              <div className="mb-1 flex items-center justify-between gap-2">
                                  <span className="text-sm font-medium text-gray-900">{p.siding_name}</span>
                                  <Badge variant="outline" className={config.className}>
                                      {config.label}
                                  </Badge>
                              </div>
                              <p className="text-xs text-gray-500">
                                  Predicted:{' '}
                                  <span className="font-semibold text-gray-700">
                                      {formatInr(p.predicted_amount_min)} – {formatInr(p.predicted_amount_max)}
                                  </span>
                              </p>
                              {p.top_recommendation && (
                                  <p className="mt-1 text-xs text-gray-600 italic">💡 {p.top_recommendation}</p>
                              )}
                          </div>
                      );
                  })}
              </CardContent>
          </Card>
      );
  }
  ```

- [ ] **3.4** Wire into dashboard page. In `resources/js/pages/dashboard.tsx`, add the import and usage in the executive dashboard section:

  **Import:**
  ```tsx
  import { PenaltyPredictionsWidget } from '@/components/dashboard/penalty-predictions-widget';
  ```

  **Add `penaltyPredictions` to the page Props type** (where other props are declared).

  **Render** in the executive section alongside the AI insights panel:
  ```tsx
  <PenaltyPredictionsWidget predictions={penaltyPredictions ?? []} />
  ```

- [ ] **3.5** Build frontend:
  ```bash
  npm run build 2>&1 | grep -E "error|✓ built"
  ```

- [ ] **3.6** Commit:
  ```bash
  git add app/Http/Controllers/Dashboard/ExecutiveDashboardController.php \
          resources/js/components/dashboard/penalty-predictions-widget.tsx \
          resources/js/pages/dashboard.tsx
  git commit -m "feat: add penalty prediction forecast widget to executive dashboard"
  ```

---

## Task 4: `rrPredictions()` Relationship on Rake Model + Surface on Railway Receipt Create Page

### Steps

- [ ] **4.1** Add relationship to `app/Models/Rake.php`. After the `wagonLoadings()` method:

  ```php
  use App\Models\RrPrediction;

  public function rrPredictions(): HasMany
  {
      return $this->hasMany(RrPrediction::class);
  }
  ```

- [ ] **4.2** Run Pint:
  ```bash
  vendor/bin/pint app/Models/Rake.php --format agent
  ```

- [ ] **4.3** Find the Railway Receipt create controller. Locate it:
  ```bash
  php artisan route:list --path=railway-receipts --method=GET 2>/dev/null | grep create
  ```
  Then open the identified controller's `create()` method and add a new prop:

  ```php
  // Add import:
  use App\Models\RrPrediction;

  // Inside the Inertia::render() call for the create page, add:
  'rrPredictions' => RrPrediction::query()
      ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds)->whereNull('deleted_at'))
      ->with('rake:id,rake_number,siding_id')
      ->orderByDesc('created_at')
      ->limit(20)
      ->get()
      ->map(fn (RrPrediction $p): array => [
          'rake_id' => $p->rake_id,
          'rake_number' => $p->rake?->rake_number,
          'predicted_weight_mt' => (float) $p->predicted_weight_mt,
          'predicted_rr_date' => $p->predicted_rr_date,
          'prediction_confidence' => (float) $p->prediction_confidence,
          'prediction_status' => $p->prediction_status,
          'variance_percent' => $p->variance_percent !== null ? (float) $p->variance_percent : null,
      ])
      ->all(),
  ```

  > **Note:** The `$sidingIds` must come from the same siding resolution the controller already uses. If the controller uses `auth()->user()` directly, use `auth()->user()->accessibleSidingIds()` or the equivalent method used elsewhere in that controller.

- [ ] **4.4** Run Pint on the modified controller.

- [ ] **4.5** Create `resources/js/components/railway-receipts/rr-predictions-panel.tsx`:

  ```tsx
  import { Badge } from '@/components/ui/badge';
  import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

  type RrPrediction = {
      rake_id: number;
      rake_number: string | null;
      predicted_weight_mt: number;
      predicted_rr_date: string | null;
      prediction_confidence: number;
      prediction_status: string;
      variance_percent: number | null;
  };

  interface Props {
      rrPredictions: RrPrediction[];
  }

  const statusColor = (status: string) => {
      if (status === 'completed') return 'bg-green-100 text-green-700';
      if (status === 'pending') return 'bg-amber-100 text-amber-700';
      return 'bg-gray-100 text-gray-700';
  };

  export function RrPredictionsPanel({ rrPredictions }: Props) {
      if (rrPredictions.length === 0) return null;

      return (
          <Card>
              <CardHeader className="pb-3">
                  <CardTitle className="text-sm font-semibold text-gray-900">🤖 AI Weight Predictions</CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="space-y-2">
                      {rrPredictions.map((p) => (
                          <div
                              key={p.rake_id}
                              className="flex items-center justify-between rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-xs"
                          >
                              <div>
                                  <span className="font-medium text-gray-800">
                                      Rake #{p.rake_number ?? p.rake_id}
                                  </span>
                                  {p.predicted_rr_date && (
                                      <span className="ml-2 text-gray-400">RR {p.predicted_rr_date}</span>
                                  )}
                              </div>
                              <div className="flex items-center gap-2">
                                  <span className="text-gray-700">
                                      {p.predicted_weight_mt.toFixed(1)} MT
                                  </span>
                                  <span className="text-gray-400">{p.prediction_confidence.toFixed(0)}% conf.</span>
                                  {p.prediction_status === 'completed' && p.variance_percent !== null && (
                                      <span
                                          className={
                                              Math.abs(p.variance_percent) <= 2
                                                  ? 'text-green-600'
                                                  : 'text-amber-600'
                                          }
                                      >
                                          Δ{p.variance_percent > 0 ? '+' : ''}
                                          {p.variance_percent.toFixed(1)}%
                                      </span>
                                  )}
                                  <Badge variant="outline" className={statusColor(p.prediction_status)}>
                                      {p.prediction_status}
                                  </Badge>
                              </div>
                          </div>
                      ))}
                  </div>
              </CardContent>
          </Card>
      );
  }
  ```

- [ ] **4.6** Add import and render in `resources/js/pages/railway-receipts/create.tsx`:

  **Add to Props:**
  ```tsx
  rrPredictions: RrPrediction[];
  ```

  **Import:**
  ```tsx
  import { RrPredictionsPanel } from '@/components/railway-receipts/rr-predictions-panel';
  ```

  **Render** in the sidebar or at the top of the form (below the page heading):
  ```tsx
  <RrPredictionsPanel rrPredictions={rrPredictions} />
  ```

- [ ] **4.7** Build frontend:
  ```bash
  npm run build 2>&1 | grep -E "error|✓ built"
  ```

- [ ] **4.8** Commit:
  ```bash
  git add app/Models/Rake.php \
          resources/js/components/railway-receipts/rr-predictions-panel.tsx \
          resources/js/pages/railway-receipts/create.tsx
  git commit -m "feat: add rrPredictions relationship and surface AI weight predictions on RR create page"
  ```

---

## Task 5: `WeeklyPenaltyReportReady` Event + Database-Mail Registration

### Steps

- [ ] **5.1** Create the event:
  ```bash
  php artisan make:event WeeklyPenaltyReportReady --no-interaction
  ```

- [ ] **5.2** Write `app/Events/WeeklyPenaltyReportReady.php`:

  ```php
  <?php

  declare(strict_types=1);

  namespace App\Events;

  use Illuminate\Foundation\Events\Dispatchable;
  use MartinPetricko\LaravelDatabaseMail\Interfaces\CanTriggerDatabaseMail;
  use MartinPetricko\LaravelDatabaseMail\Interfaces\TriggersDatabaseMail;
  use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

  final class WeeklyPenaltyReportReady implements TriggersDatabaseMail, CanTriggerDatabaseMail
  {
      use Dispatchable;

      /**
       * @param  array{
       *   period_label: string,
       *   total_penalties_inr: float,
       *   total_penalties_count: int,
       *   preventable_percent: float,
       *   top_operators: list<array{name: string, amount_inr: float}>,
       *   vs_prior_week_inr: float,
       *   vs_prior_week_percent: float,
       *   sidings_summary: list<array{siding_name: string, total_inr: float, count: int}>,
       * }  $reportData
       * @param  list<array{email: string, name: string}>  $recipients
       */
      public function __construct(
          public readonly array $reportData,
          public readonly array $recipients,
      ) {}

      public static function getName(): string
      {
          return 'Weekly Penalty Report';
      }

      public static function getDescription(): string
      {
          return 'Dispatched every Monday morning with the previous week\'s penalty summary, comparison to prior week, and top 3 operators by penalty amount.';
      }

      /**
       * @return list<Recipient>
       */
      public function getRecipients(): array
      {
          return array_map(
              fn (array $r): Recipient => new Recipient($r['email'], $r['name']),
              $this->recipients,
          );
      }
  }
  ```

- [ ] **5.3** Register the event in `config/database-mail.php`. Open the file and add to the `'events'` array:
  ```php
  \App\Events\WeeklyPenaltyReportReady::class,
  ```

- [ ] **5.4** Run Pint:
  ```bash
  vendor/bin/pint app/Events/WeeklyPenaltyReportReady.php --format agent
  ```

- [ ] **5.5** Commit:
  ```bash
  git add app/Events/WeeklyPenaltyReportReady.php config/database-mail.php
  git commit -m "feat: add WeeklyPenaltyReportReady event with database-mail integration"
  ```

---

## Task 6: `SendWeeklyPenaltyReport` Artisan Command + Schedule

### Steps

- [ ] **6.1** Create the command:
  ```bash
  php artisan make:command SendWeeklyPenaltyReport --no-interaction
  ```

- [ ] **6.2** Write `app/Console/Commands/SendWeeklyPenaltyReport.php`:

  ```php
  <?php

  declare(strict_types=1);

  namespace App\Console\Commands;

  use App\Events\WeeklyPenaltyReportReady;
  use App\Models\Penalty;
  use App\Models\User;
  use Illuminate\Console\Command;
  use Illuminate\Support\Facades\DB;

  final class SendWeeklyPenaltyReport extends Command
  {
      protected $signature = 'rrmcs:send-weekly-penalty-report';

      protected $description = 'Dispatch the WeeklyPenaltyReportReady event with last 7-day penalty summary.';

      public function handle(): int
      {
          $this->info('Gathering last 7 days of penalty data…');

          $thisWeek = $this->queryWeekData(0);
          $priorWeek = $this->queryWeekData(1);

          $vsInr = $thisWeek['total_inr'] - $priorWeek['total_inr'];
          $vsPct = $priorWeek['total_inr'] > 0
              ? round(($vsInr / $priorWeek['total_inr']) * 100, 1)
              : 0.0;

          $preventable = $thisWeek['total_count'] > 0
              ? round(($thisWeek['preventable_count'] / $thisWeek['total_count']) * 100, 1)
              : 0.0;

          $reportData = [
              'period_label' => now()->subDays(7)->format('d M Y').' – '.now()->subDay()->format('d M Y'),
              'total_penalties_inr' => $thisWeek['total_inr'],
              'total_penalties_count' => $thisWeek['total_count'],
              'preventable_percent' => $preventable,
              'top_operators' => $thisWeek['top_operators'],
              'vs_prior_week_inr' => $vsInr,
              'vs_prior_week_percent' => $vsPct,
              'sidings_summary' => $thisWeek['sidings_summary'],
          ];

          $recipients = User::query()
              ->where('is_active', true)
              ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'manager']))
              ->get(['name', 'email'])
              ->map(fn (User $u): array => ['email' => $u->email, 'name' => $u->name])
              ->all();

          if ($recipients === []) {
              $this->warn('No eligible recipients found. Skipping dispatch.');

              return self::SUCCESS;
          }

          WeeklyPenaltyReportReady::dispatch($reportData, $recipients);

          $this->info("WeeklyPenaltyReportReady dispatched to ".count($recipients)." recipients.");

          return self::SUCCESS;
      }

      /**
       * @return array{
       *   total_inr: float,
       *   total_count: int,
       *   preventable_count: int,
       *   top_operators: list<array{name: string, amount_inr: float}>,
       *   sidings_summary: list<array{siding_name: string, total_inr: float, count: int}>,
       * }
       */
      private function queryWeekData(int $weeksAgo): array
      {
          $from = now()->subWeeks($weeksAgo + 1)->startOfWeek();
          $to = now()->subWeeks($weeksAgo)->startOfWeek();

          $base = Penalty::query()
              ->whereNull('penalties.deleted_at')
              ->where('penalty_date', '>=', $from)
              ->where('penalty_date', '<', $to);

          $totals = $base->clone()
              ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(penalty_amount), 0) as total_inr')
              ->first();

          $preventable = $base->clone()
              ->whereIn('penalty_status', ['waived', 'disputed'])
              ->count();

          $topOperators = $base->clone()
              ->whereNotNull('responsible_party')
              ->selectRaw('responsible_party as name, SUM(penalty_amount) as amount_inr')
              ->groupBy('responsible_party')
              ->orderByDesc('amount_inr')
              ->limit(3)
              ->get()
              ->map(fn ($r): array => [
                  'name' => (string) $r->name,
                  'amount_inr' => (float) $r->amount_inr,
              ])
              ->all();

          $sidings = $base->clone()
              ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
              ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
              ->selectRaw('sidings.name as siding_name, COUNT(*) as count, SUM(penalties.penalty_amount) as total_inr')
              ->groupBy('sidings.name')
              ->orderByDesc('total_inr')
              ->limit(5)
              ->get()
              ->map(fn ($r): array => [
                  'siding_name' => (string) $r->siding_name,
                  'total_inr' => (float) $r->total_inr,
                  'count' => (int) $r->count,
              ])
              ->all();

          return [
              'total_inr' => (float) ($totals->total_inr ?? 0),
              'total_count' => (int) ($totals->total_count ?? 0),
              'preventable_count' => $preventable,
              'top_operators' => $topOperators,
              'sidings_summary' => $sidings,
          ];
      }
  }
  ```

- [ ] **6.3** Register schedule in `routes/console.php` — append below the existing weekly schedule:
  ```php
  Schedule::command('rrmcs:send-weekly-penalty-report')->weekly()->mondays()->at('08:00');
  ```

- [ ] **6.4** Run Pint:
  ```bash
  vendor/bin/pint app/Console/Commands/SendWeeklyPenaltyReport.php --format agent
  ```

- [ ] **6.5** Verify the command runs without error:
  ```bash
  php artisan rrmcs:send-weekly-penalty-report --dry-run 2>&1 || php artisan rrmcs:send-weekly-penalty-report 2>&1
  ```

- [ ] **6.6** Commit:
  ```bash
  git add app/Console/Commands/SendWeeklyPenaltyReport.php routes/console.php
  git commit -m "feat: add SendWeeklyPenaltyReport command and Monday 08:00 schedule"
  ```

---

## Task 7: Overload Pattern Learning — Backend Prop + Frontend Widget

### Steps

- [ ] **7.1** Add `buildOverloadPatterns()` private method to `ExecutiveDashboardController`:

  ```php
  /**
   * For each siding, find the top 3 wagon types by overload rate over last 30 days.
   *
   * @param  array<int>  $sidingIds
   * @return list<array{siding_name: string, patterns: list<array{wagon_type: string, overload_rate_percent: float, overloaded_count: int, total_count: int}>}>
   */
  private function buildOverloadPatterns(array $sidingIds): array
  {
      if ($sidingIds === []) {
          return [];
      }

      $placeholders = implode(',', array_fill(0, count($sidingIds), '?'));

      $rows = DB::select(
          <<<SQL
          SELECT
              s.id   AS siding_id,
              s.name AS siding_name,
              w.wagon_type,
              COUNT(wl.id)                                                   AS total_count,
              SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt THEN 1 ELSE 0 END) AS overloaded_count,
              ROUND(
                  SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt THEN 1 ELSE 0 END)::numeric
                  / GREATEST(COUNT(wl.id), 1) * 100,
                  1
              ) AS overload_rate_percent
          FROM wagon_loading wl
          JOIN wagons w  ON w.id  = wl.wagon_id
          JOIN rakes  r  ON r.id  = wl.rake_id
          JOIN sidings s ON s.id  = r.siding_id
          WHERE r.siding_id IN ({$placeholders})
            AND r.deleted_at  IS NULL
            AND wl.created_at >= NOW() - INTERVAL '30 days'
            AND w.wagon_type  IS NOT NULL
            AND w.pcc_weight_mt IS NOT NULL
          GROUP BY s.id, s.name, w.wagon_type
          HAVING COUNT(wl.id) >= 3
          ORDER BY s.id, overload_rate_percent DESC
          SQL,
          $sidingIds,
      );

      // Group by siding and keep top 3 per siding
      $bySiding = [];
      foreach ($rows as $row) {
          $sid = (int) $row->siding_id;
          if (! isset($bySiding[$sid])) {
              $bySiding[$sid] = [
                  'siding_name' => $row->siding_name,
                  'patterns' => [],
              ];
          }
          if (count($bySiding[$sid]['patterns']) < 3) {
              $bySiding[$sid]['patterns'][] = [
                  'wagon_type' => $row->wagon_type,
                  'overload_rate_percent' => (float) $row->overload_rate_percent,
                  'overloaded_count' => (int) $row->overloaded_count,
                  'total_count' => (int) $row->total_count,
              ];
          }
      }

      return array_values($bySiding);
  }
  ```

  **Add prop in `__invoke()` inside `Inertia::render()`:**
  ```php
  'overloadPatterns' => $this->buildOverloadPatterns($resolved['filteredSidingIds']),
  ```

- [ ] **7.2** Run Pint:
  ```bash
  vendor/bin/pint app/Http/Controllers/Dashboard/ExecutiveDashboardController.php --format agent
  ```

- [ ] **7.3** Create `resources/js/components/dashboard/overload-patterns-widget.tsx`:

  ```tsx
  import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

  type OverloadPattern = {
      wagon_type: string;
      overload_rate_percent: number;
      overloaded_count: number;
      total_count: number;
  };

  type SidingPattern = {
      siding_name: string;
      patterns: OverloadPattern[];
  };

  interface Props {
      overloadPatterns: SidingPattern[];
  }

  function TrendArrow({ rate }: { rate: number }) {
      if (rate >= 30) return <span className="text-red-500">↑</span>;
      if (rate >= 15) return <span className="text-amber-500">→</span>;
      return <span className="text-green-500">↓</span>;
  }

  export function OverloadPatternsWidget({ overloadPatterns }: Props) {
      if (overloadPatterns.length === 0) return null;

      return (
          <Card>
              <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-sm font-semibold text-gray-900">
                      <span>🧠</span>
                      <span>AI Risk Patterns — 30-Day Overload Trends</span>
                  </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                  {overloadPatterns.map((siding) => (
                      <div key={siding.siding_name}>
                          <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                              {siding.siding_name}
                          </p>
                          <div className="space-y-1">
                              {siding.patterns.map((p) => (
                                  <div
                                      key={p.wagon_type}
                                      className="flex items-center justify-between rounded-md bg-gray-50 px-2 py-1 text-xs"
                                  >
                                      <span className="font-medium text-gray-700">{p.wagon_type}</span>
                                      <div className="flex items-center gap-1">
                                          <TrendArrow rate={p.overload_rate_percent} />
                                          <span
                                              className={
                                                  p.overload_rate_percent >= 30
                                                      ? 'font-bold text-red-600'
                                                      : p.overload_rate_percent >= 15
                                                        ? 'font-semibold text-amber-600'
                                                        : 'text-green-600'
                                              }
                                          >
                                              {p.overload_rate_percent.toFixed(1)}%
                                          </span>
                                          <span className="text-gray-400">
                                              ({p.overloaded_count}/{p.total_count})
                                          </span>
                                      </div>
                                  </div>
                              ))}
                          </div>
                      </div>
                  ))}
              </CardContent>
          </Card>
      );
  }
  ```

- [ ] **7.4** Wire into `resources/js/pages/dashboard.tsx`. Add to Props type, import, and render in the Command Center section:

  **Import:**
  ```tsx
  import { OverloadPatternsWidget } from '@/components/dashboard/overload-patterns-widget';
  ```

  **In Props:**
  ```tsx
  overloadPatterns: SidingPattern[];
  ```

  **Render:**
  ```tsx
  <OverloadPatternsWidget overloadPatterns={overloadPatterns ?? []} />
  ```

- [ ] **7.5** Build frontend:
  ```bash
  npm run build 2>&1 | grep -E "error|✓ built"
  ```

- [ ] **7.6** Commit:
  ```bash
  git add app/Http/Controllers/Dashboard/ExecutiveDashboardController.php \
          resources/js/components/dashboard/overload-patterns-widget.tsx \
          resources/js/pages/dashboard.tsx
  git commit -m "feat: add AI overload pattern risk widget to executive dashboard"
  ```

---

## Task 8: Final Build, Pint, Tests

### Steps

- [ ] **8.1** Run Pint across all modified PHP files:
  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **8.2** Static analysis:
  ```bash
  ./vendor/bin/phpstan analyse --memory-limit=512M 2>&1 | tail -20
  ```

- [ ] **8.3** Write a Pest feature test for `GenerateLoadingRecommendationAction`:
  ```bash
  php artisan make:test --pest GenerateLoadingRecommendationActionTest --no-interaction
  ```

  Write `tests/Feature/GenerateLoadingRecommendationActionTest.php`:

  ```php
  <?php

  declare(strict_types=1);

  use App\Actions\GenerateLoadingRecommendationAction;
  use App\Models\Rake;
  use App\Services\PrismService;
  use Illuminate\Support\Facades\Cache;

  it('returns null when prism is unavailable', function (): void {
      $prism = Mockery::mock(PrismService::class);
      $prism->shouldReceive('isAvailable')->once()->andReturn(false);

      $rake = Rake::factory()->create();

      $action = new GenerateLoadingRecommendationAction($prism);
      $result = $action->handle($rake);

      expect($result)->toBeNull();
  });

  it('returns cached result on second call', function (): void {
      $rake = Rake::factory()->create();
      $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

      Cache::put($cacheKey, 'Cached recommendation text', 21600);

      $prism = Mockery::mock(PrismService::class);
      $prism->shouldNotReceive('isAvailable');

      $action = new GenerateLoadingRecommendationAction($prism);
      $result = $action->handle($rake);

      expect($result)->toBe('Cached recommendation text');
  });

  it('returns null when cached value is unavailable sentinel', function (): void {
      $rake = Rake::factory()->create();
      $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

      Cache::put($cacheKey, '__unavailable__', 21600);

      $prism = Mockery::mock(PrismService::class);
      $prism->shouldNotReceive('isAvailable');

      $action = new GenerateLoadingRecommendationAction($prism);

      expect($action->handle($rake))->toBeNull();
  });
  ```

- [ ] **8.4** Write a Pest feature test for `SendWeeklyPenaltyReport` command:
  ```bash
  php artisan make:test --pest SendWeeklyPenaltyReportTest --no-interaction
  ```

  Write `tests/Feature/SendWeeklyPenaltyReportTest.php`:

  ```php
  <?php

  declare(strict_types=1);

  use App\Events\WeeklyPenaltyReportReady;
  use Illuminate\Support\Facades\Event;

  it('dispatches WeeklyPenaltyReportReady event when managers exist', function (): void {
      Event::fake([WeeklyPenaltyReportReady::class]);

      $manager = \App\Models\User::factory()->create(['is_active' => true]);
      $manager->assignRole('manager');

      $this->artisan('rrmcs:send-weekly-penalty-report')
          ->assertExitCode(0);

      Event::assertDispatched(WeeklyPenaltyReportReady::class);
  });

  it('skips dispatch when no eligible recipients', function (): void {
      Event::fake([WeeklyPenaltyReportReady::class]);

      $this->artisan('rrmcs:send-weekly-penalty-report')
          ->assertExitCode(0);

      Event::assertNotDispatched(WeeklyPenaltyReportReady::class);
  });
  ```

- [ ] **8.5** Run tests:
  ```bash
  php artisan test --compact --filter="GenerateLoadingRecommendation|SendWeeklyPenalty" 2>&1
  ```

- [ ] **8.6** Final build check:
  ```bash
  npm run build 2>&1 | grep -E "error|✓ built"
  ```

- [ ] **8.7** Final commit:
  ```bash
  git add tests/Feature/GenerateLoadingRecommendationActionTest.php \
          tests/Feature/SendWeeklyPenaltyReportTest.php
  git commit -m "test: add Pest tests for GenerateLoadingRecommendationAction and SendWeeklyPenaltyReport"
  ```

---

## Summary

| Task | Feature | Files Changed |
|------|---------|---------------|
| 1 | Prism loading recommendation action | `app/Actions/GenerateLoadingRecommendationAction.php` |
| 2 | Deferred prop + UI card on rake loader | `RakeLoaderController.php`, `loading.tsx` |
| 3 | PenaltyPrediction dashboard widget | `ExecutiveDashboardController.php`, `penalty-predictions-widget.tsx`, `dashboard.tsx` |
| 4 | RrPredictions relationship + RR create page | `Rake.php`, `rr-predictions-panel.tsx`, `create.tsx`, RR controller |
| 5 | WeeklyPenaltyReportReady event | `WeeklyPenaltyReportReady.php`, `config/database-mail.php` |
| 6 | SendWeeklyPenaltyReport command + schedule | `SendWeeklyPenaltyReport.php`, `routes/console.php` |
| 7 | Overload pattern risk widget | `ExecutiveDashboardController.php`, `overload-patterns-widget.tsx`, `dashboard.tsx` |
| 8 | Pint + static analysis + Pest tests | `tests/Feature/…` |

**Key patterns to preserve:**
- Prism: always check `isAvailable()`, cache with sentinel `'__unavailable__'`, use `Cache::has()` guard before calling the API
- PostgreSQL: use `EXTRACT`, `GREATEST`, `::numeric` casts, `INTERVAL '30 days'`
- Always `whereNull('penalties.deleted_at')` in raw SQL joins; Eloquent SoftDeletes handles it automatically
- `wagon_loading` is the raw table name (not `wagon_loadings`)
- Inertia deferred props: `Inertia::defer(fn () => ...)` — frontend prop is `undefined` while loading, then the resolved value
