# Loadrite Evidence Endpoints — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire four currently-unused Loadrite InsightHQ endpoints (`Downtime`, `Conveyor`, `Haul`, `Positions`) into the Saloon connector, and use the Downtime feed to auto-flag DEM force-majeure dispute candidates against the existing `penalty_reconciliations` table.

**Architecture:** Four new Saloon `Request` classes follow the existing `GetNewWeightEventsRequest` pattern. A new `loadrite_downtime_events` cache table stores raw downtime windows per siding so the stitcher does not hit the API on every reconciliation. A daily `FetchLoadriteDowntimeJob` per active siding populates the cache. A new `StitchForceMajeureDisputesAction` cross-references downtime windows with each rake's loading window and marks `penalty_reconciliations.dispute_candidate = true` with a structured note when overlap exceeds 15 minutes. Conveyor / Haul / Positions request classes are wired (scaffolded + tested with `MockClient`) so future stitchers can reuse them without re-touching the connector. No code path is conditional on a feature flag — activation happens automatically once a siding has `loadrite_settings`.

**Tech Stack:** Laravel 13, Saloon, Horizon 5, PostgreSQL, Pest 4, `wildside/userstamps`, Filament 5

**Source spec:** `docs/superpowers/specs/2026-04-30-loadrite-api-integration-design.md` (extended)

**Depends on:** Loadrite connector + LoadriteSetting + LoadriteTokenManager already in `feature/laravel-13`. `penalty_reconciliations` table + `PenaltyReconciliation` model + `ReconcilePenaltyHeadsAction` already in `feature/laravel-13`.

---

## File Structure (created or modified)

**Created:**
- `app/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequest.php`
- `app/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequest.php`
- `app/Http/Integrations/Loadrite/Requests/GetHaulEventsRequest.php`
- `app/Http/Integrations/Loadrite/Requests/GetPositionsRequest.php`
- `database/migrations/2026_05_02_100001_create_loadrite_downtime_events_table.php`
- `app/Models/LoadriteDowntimeEvent.php`
- `database/factories/LoadriteDowntimeEventFactory.php`
- `app/Jobs/FetchLoadriteDowntimeJob.php`
- `app/Actions/ForceMajeureStitchOutcome.php` (readonly DTO)
- `app/Actions/StitchForceMajeureDisputesAction.php`
- `app/Console/Commands/LoadriteFetchDowntimeCommand.php`
- `app/Console/Commands/StitchForceMajeureDisputesCommand.php`
- `tests/Unit/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequestTest.php`
- `tests/Unit/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequestTest.php`
- `tests/Unit/Http/Integrations/Loadrite/Requests/GetHaulEventsRequestTest.php`
- `tests/Unit/Http/Integrations/Loadrite/Requests/GetPositionsRequestTest.php`
- `tests/Feature/Jobs/FetchLoadriteDowntimeJobTest.php`
- `tests/Unit/Actions/StitchForceMajeureDisputesActionTest.php`
- `tests/Feature/Console/StitchForceMajeureDisputesCommandTest.php`
- `docs/developer/backend/actions/StitchForceMajeureDisputesAction.md`

**Modified:**
- The project's schedule file (`app/Console/Kernel.php` or `routes/console.php` — discovered in Task 11) — schedule both new commands
- `docs/.manifest.json` — register new Action doc

---

### Task 1: GetDowntimeEventsRequest (v3 — required from/to)

**Files:**
- Create: `app/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequest.php`
- Test: `tests/Unit/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequestTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequestTest.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\GetDowntimeEventsRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

it('hits the v3 downtime endpoint with required query params', function (): void {
    MockClient::global([
        MockResponse::make([
            ['DowntimeId' => 1, 'StartLocalTime' => '2026-04-30 09:00:00', 'EndLocalTime' => '2026-04-30 09:25:00', 'ReasonName' => 'Plant Stoppage'],
        ], 200),
    ]);

    $connector = new LoadriteConnector('token');
    $response = $connector->send(new GetDowntimeEventsRequest('Dumka', '2026-04-30 00:00:00', '2026-04-30 23:59:59'));

    expect($response->successful())->toBeTrue();

    $request = $response->getPendingRequest();
    expect($request->getUrl())->toContain('/api/v3/Downtime');
    expect($request->query()->all())->toMatchArray([
        'Site' => 'Dumka',
        'FromLocalTime' => '2026-04-30 00:00:00',
        'ToLocalTime' => '2026-04-30 23:59:59',
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GetDowntimeEventsRequestTest --compact`
Expected: FAIL with "Class GetDowntimeEventsRequest not found".

- [ ] **Step 3: Create the request class**

Create `app/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetDowntimeEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly string $fromLocalTime,
        private readonly string $toLocalTime,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v3/Downtime';
    }

    protected function defaultQuery(): array
    {
        return [
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GetDowntimeEventsRequestTest --compact`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequest.php \
        tests/Unit/Http/Integrations/Loadrite/Requests/GetDowntimeEventsRequestTest.php
git commit -m "feat(loadrite): add GetDowntimeEventsRequest"
```

---

### Task 2: GetConveyorEventsRequest

**Files:**
- Create: `app/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequest.php`
- Test: `tests/Unit/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequestTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\GetConveyorEventsRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

it('hits the conveyor endpoint with optional date range', function (): void {
    MockClient::global([MockResponse::make([], 200)]);

    $connector = new LoadriteConnector('token');
    $response = $connector->send(new GetConveyorEventsRequest('Dumka', '2026-04-30 00:00:00', '2026-04-30 23:59:59'));

    expect($response->successful())->toBeTrue();

    $request = $response->getPendingRequest();
    expect($request->getUrl())->toContain('/api/v2/Conveyor');
    expect($request->query()->all())->toMatchArray([
        'Site' => 'Dumka',
        'FromLocalTime' => '2026-04-30 00:00:00',
        'ToLocalTime' => '2026-04-30 23:59:59',
    ]);
});

it('omits null date params from the query', function (): void {
    MockClient::global([MockResponse::make([], 200)]);

    $connector = new LoadriteConnector('token');
    $response = $connector->send(new GetConveyorEventsRequest('Dumka'));

    expect($response->getPendingRequest()->query()->all())->toBe(['Site' => 'Dumka']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GetConveyorEventsRequestTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the request class**

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetConveyorEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly ?string $fromLocalTime = null,
        private readonly ?string $toLocalTime = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Conveyor';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ], fn ($v) => $v !== null);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GetConveyorEventsRequestTest --compact`. Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequest.php \
        tests/Unit/Http/Integrations/Loadrite/Requests/GetConveyorEventsRequestTest.php
git commit -m "feat(loadrite): add GetConveyorEventsRequest"
```

---

### Task 3: GetHaulEventsRequest

**Files:**
- Create: `app/Http/Integrations/Loadrite/Requests/GetHaulEventsRequest.php`
- Test: `tests/Unit/Http/Integrations/Loadrite/Requests/GetHaulEventsRequestTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\GetHaulEventsRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

it('hits the haul endpoint with optional date range', function (): void {
    MockClient::global([MockResponse::make([], 200)]);

    $connector = new LoadriteConnector('token');
    $response = $connector->send(new GetHaulEventsRequest('Dumka', '2026-04-30 00:00:00', '2026-04-30 23:59:59'));

    expect($response->successful())->toBeTrue();

    $request = $response->getPendingRequest();
    expect($request->getUrl())->toContain('/api/v2/Haul');
    expect($request->query()->all())->toMatchArray([
        'Site' => 'Dumka',
        'FromLocalTime' => '2026-04-30 00:00:00',
        'ToLocalTime' => '2026-04-30 23:59:59',
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GetHaulEventsRequestTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the request class**

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetHaulEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly ?string $fromLocalTime = null,
        private readonly ?string $toLocalTime = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Haul';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ], fn ($v) => $v !== null);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GetHaulEventsRequestTest --compact`. Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Integrations/Loadrite/Requests/GetHaulEventsRequest.php \
        tests/Unit/Http/Integrations/Loadrite/Requests/GetHaulEventsRequestTest.php
git commit -m "feat(loadrite): add GetHaulEventsRequest"
```

---

### Task 4: GetPositionsRequest (7-day historic limit)

**Files:**
- Create: `app/Http/Integrations/Loadrite/Requests/GetPositionsRequest.php`
- Test: `tests/Unit/Http/Integrations/Loadrite/Requests/GetPositionsRequestTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\GetPositionsRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

it('hits the positions endpoint with optional date range', function (): void {
    MockClient::global([MockResponse::make([], 200)]);

    $connector = new LoadriteConnector('token');
    $response = $connector->send(new GetPositionsRequest('Dumka', '2026-04-29 00:00:00', '2026-04-30 23:59:59'));

    expect($response->successful())->toBeTrue();

    $request = $response->getPendingRequest();
    expect($request->getUrl())->toContain('/api/v2/Positions');
    expect($request->query()->all())->toMatchArray([
        'Site' => 'Dumka',
        'FromLocalTime' => '2026-04-29 00:00:00',
        'ToLocalTime' => '2026-04-30 23:59:59',
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GetPositionsRequestTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the request class**

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Loadrite enforces a 7-day historic-data limit on this endpoint.
 * Callers must pass a `from` no older than now()-7 days.
 */
final class GetPositionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly ?string $fromLocalTime = null,
        private readonly ?string $toLocalTime = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Positions';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ], fn ($v) => $v !== null);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=GetPositionsRequestTest --compact`. Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Integrations/Loadrite/Requests/GetPositionsRequest.php \
        tests/Unit/Http/Integrations/Loadrite/Requests/GetPositionsRequestTest.php
git commit -m "feat(loadrite): add GetPositionsRequest"
```

---

### Task 5: Migration — `loadrite_downtime_events` cache table

**Files:**
- Create: `database/migrations/2026_05_02_100001_create_loadrite_downtime_events_table.php`

- [ ] **Step 1: Generate migration**

Run: `php artisan make:migration create_loadrite_downtime_events_table --no-interaction`

- [ ] **Step 2: Fill in the migration**

Replace the generated content:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadrite_downtime_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->constrained('sidings')->cascadeOnDelete();
            $table->string('downtime_id', 64)->comment('Loadrite-side primary key');
            $table->dateTime('start_local_time');
            $table->dateTime('end_local_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('reason_name', 128)->nullable();
            $table->string('sub_reason_name', 128)->nullable();
            $table->string('equipment_name', 128)->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['siding_id', 'downtime_id']);
            $table->index(['siding_id', 'start_local_time']);
            $table->index(['siding_id', 'end_local_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadrite_downtime_events');
    }
};
```

- [ ] **Step 3: Run the migration**

Run: `php artisan migrate --no-interaction`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_02_100001_create_loadrite_downtime_events_table.php
git commit -m "feat(loadrite): create loadrite_downtime_events cache table"
```

---

### Task 6: `LoadriteDowntimeEvent` model + factory

**Files:**
- Create: `app/Models/LoadriteDowntimeEvent.php`
- Create: `database/factories/LoadriteDowntimeEventFactory.php`

- [ ] **Step 1: Generate model + factory**

Run: `php artisan make:model LoadriteDowntimeEvent --factory --no-interaction`

- [ ] **Step 2: Fill in the model**

Replace `app/Models/LoadriteDowntimeEvent.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LoadriteDowntimeEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $siding_id
 * @property string $downtime_id
 * @property \Carbon\CarbonImmutable $start_local_time
 * @property \Carbon\CarbonImmutable|null $end_local_time
 * @property int|null $duration_minutes
 * @property string|null $reason_name
 * @property string|null $sub_reason_name
 * @property string|null $equipment_name
 * @property array $raw_payload
 */
final class LoadriteDowntimeEvent extends Model
{
    /** @use HasFactory<LoadriteDowntimeEventFactory> */
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'downtime_id',
        'start_local_time',
        'end_local_time',
        'duration_minutes',
        'reason_name',
        'sub_reason_name',
        'equipment_name',
        'raw_payload',
    ];

    protected $casts = [
        'start_local_time' => 'immutable_datetime',
        'end_local_time' => 'immutable_datetime',
        'duration_minutes' => 'integer',
        'raw_payload' => 'array',
    ];

    /** @return BelongsTo<Siding, self> */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    protected static function newFactory(): LoadriteDowntimeEventFactory
    {
        return LoadriteDowntimeEventFactory::new();
    }
}
```

- [ ] **Step 3: Fill in the factory**

Replace `database/factories/LoadriteDowntimeEventFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoadriteDowntimeEvent;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoadriteDowntimeEvent> */
final class LoadriteDowntimeEventFactory extends Factory
{
    protected $model = LoadriteDowntimeEvent::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', '-1 day');
        $duration = $this->faker->numberBetween(5, 180);

        return [
            'siding_id' => Siding::factory(),
            'downtime_id' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'start_local_time' => $start,
            'end_local_time' => (clone $start)->modify("+{$duration} minutes"),
            'duration_minutes' => $duration,
            'reason_name' => $this->faker->randomElement(['Plant Stoppage', 'Maintenance', 'Weather', 'Power Outage']),
            'sub_reason_name' => null,
            'equipment_name' => $this->faker->randomElement(['Conveyor 1', 'Crusher A', null]),
            'raw_payload' => [],
        ];
    }
}
```

- [ ] **Step 4: Sanity-check the model**

Run:
```bash
php artisan tinker --execute 'echo \App\Models\LoadriteDowntimeEvent::factory()->make()->reason_name;'
```
Expected: prints one of the four reason strings.

- [ ] **Step 5: Commit**

```bash
git add app/Models/LoadriteDowntimeEvent.php database/factories/LoadriteDowntimeEventFactory.php
git commit -m "feat(loadrite): add LoadriteDowntimeEvent model + factory"
```

---

### Task 7: `FetchLoadriteDowntimeJob` — populate cache table

**Files:**
- Create: `app/Jobs/FetchLoadriteDowntimeJob.php`
- Test: `tests/Feature/Jobs/FetchLoadriteDowntimeJobTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Jobs\FetchLoadriteDowntimeJob;
use App\Models\LoadriteDowntimeEvent;
use App\Models\LoadriteSetting;
use App\Models\Siding;
use App\Services\LoadriteTokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

beforeEach(function (): void {
    $this->siding = Siding::factory()->create();
    LoadriteSetting::factory()->create([
        'siding_id' => $this->siding->id,
        'site_name' => 'Dumka',
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHour(),
    ]);
});

it('upserts downtime events into the cache table', function (): void {
    MockClient::global([
        MockResponse::make([
            [
                'DowntimeId' => '101',
                'StartLocalTime' => '2026-04-30 09:00:00',
                'EndLocalTime' => '2026-04-30 09:25:00',
                'DurationInMinutes' => 25,
                'ReasonName' => 'Plant Stoppage',
                'SubReasonName' => 'Belt Failure',
                'EquipmentName' => 'Conveyor 1',
            ],
            [
                'DowntimeId' => '102',
                'StartLocalTime' => '2026-04-30 14:00:00',
                'EndLocalTime' => null,
                'DurationInMinutes' => null,
                'ReasonName' => 'Weather',
            ],
        ], 200),
    ]);

    (new FetchLoadriteDowntimeJob($this->siding->id))->handle(app(LoadriteTokenManager::class));

    expect(LoadriteDowntimeEvent::count())->toBe(2);

    $first = LoadriteDowntimeEvent::where('downtime_id', '101')->first();
    expect($first->reason_name)->toBe('Plant Stoppage')
        ->and($first->duration_minutes)->toBe(25)
        ->and($first->equipment_name)->toBe('Conveyor 1');
});

it('is idempotent across runs', function (): void {
    $payload = [
        [
            'DowntimeId' => '101',
            'StartLocalTime' => '2026-04-30 09:00:00',
            'EndLocalTime' => '2026-04-30 09:25:00',
            'DurationInMinutes' => 25,
            'ReasonName' => 'Plant Stoppage',
        ],
    ];

    MockClient::global([
        MockResponse::make($payload, 200),
        MockResponse::make($payload, 200),
    ]);

    (new FetchLoadriteDowntimeJob($this->siding->id))->handle(app(LoadriteTokenManager::class));
    (new FetchLoadriteDowntimeJob($this->siding->id))->handle(app(LoadriteTokenManager::class));

    expect(LoadriteDowntimeEvent::count())->toBe(1);
});

it('logs and exits gracefully when API returns non-200', function (): void {
    MockClient::global([MockResponse::make(['error' => 'forbidden'], 403)]);

    (new FetchLoadriteDowntimeJob($this->siding->id))->handle(app(LoadriteTokenManager::class));

    expect(LoadriteDowntimeEvent::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FetchLoadriteDowntimeJobTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the job**

Create `app/Jobs/FetchLoadriteDowntimeJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Integrations\Loadrite\Requests\GetDowntimeEventsRequest;
use App\Models\LoadriteDowntimeEvent;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FetchLoadriteDowntimeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private readonly int $sidingId,
        private readonly int $lookbackDays = 7,
    ) {}

    public function handle(LoadriteTokenManager $tokenManager): void
    {
        $setting = LoadriteSetting::query()
            ->where('siding_id', $this->sidingId)
            ->firstOrFail();

        $from = now()->subDays($this->lookbackDays)->format('Y-m-d H:i:s');
        $to = now()->format('Y-m-d H:i:s');

        try {
            $connector = $tokenManager->getConnector($this->sidingId);
            $response = $connector->send(new GetDowntimeEventsRequest($setting->site_name, $from, $to));
        } catch (Throwable $e) {
            Log::warning('loadrite.downtime.fetch.exception', [
                'siding_id' => $this->sidingId,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('loadrite.downtime.fetch.failed', [
                'siding_id' => $this->sidingId,
                'status' => $response->status(),
            ]);

            return;
        }

        $events = $response->json() ?? [];

        foreach ($events as $event) {
            LoadriteDowntimeEvent::updateOrCreate(
                [
                    'siding_id' => $this->sidingId,
                    'downtime_id' => (string) ($event['DowntimeId'] ?? ''),
                ],
                [
                    'start_local_time' => $event['StartLocalTime'] ?? null,
                    'end_local_time' => $event['EndLocalTime'] ?? null,
                    'duration_minutes' => isset($event['DurationInMinutes']) ? (int) $event['DurationInMinutes'] : null,
                    'reason_name' => $event['ReasonName'] ?? null,
                    'sub_reason_name' => $event['SubReasonName'] ?? null,
                    'equipment_name' => $event['EquipmentName'] ?? null,
                    'raw_payload' => $event,
                ],
            );
        }

        Log::info('loadrite.downtime.fetched', [
            'siding_id' => $this->sidingId,
            'count' => count($events),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FetchLoadriteDowntimeJobTest --compact`. Expected: PASS, 3 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/FetchLoadriteDowntimeJob.php tests/Feature/Jobs/FetchLoadriteDowntimeJobTest.php
git commit -m "feat(loadrite): add FetchLoadriteDowntimeJob"
```

---

### Task 8: `ForceMajeureStitchOutcome` DTO

**Files:**
- Create: `app/Actions/ForceMajeureStitchOutcome.php`

- [ ] **Step 1: Create the DTO**

```php
<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Pure result struct for StitchForceMajeureDisputesAction.
 * Compute-then-apply split: callers can dry-run before persisting.
 */
final readonly class ForceMajeureStitchOutcome
{
    /**
     * @param  list<array{rake_id: int, downtime_event_id: int, overlap_minutes: int, reason: string}>  $candidates
     */
    public function __construct(
        public array $candidates,
        public int $rakesScanned,
        public int $downtimeEventsConsidered,
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Actions/ForceMajeureStitchOutcome.php
git commit -m "feat(penalty): add ForceMajeureStitchOutcome DTO"
```

---

### Task 9: `StitchForceMajeureDisputesAction`

**Files:**
- Create: `app/Actions/StitchForceMajeureDisputesAction.php`
- Test: `tests/Unit/Actions/StitchForceMajeureDisputesActionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\StitchForceMajeureDisputesAction;
use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;

beforeEach(function (): void {
    $this->siding = Siding::factory()->create();
    $this->action = app(StitchForceMajeureDisputesAction::class);
});

it('flags reconciliation when downtime overlaps loading window > 15 min', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'billed_amount' => 25000,
        'predicted_amount' => 5000,
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $outcome = $this->action->handle();

    $reconciliation->refresh();
    expect($outcome->candidates)->toHaveCount(1)
        ->and($reconciliation->dispute_candidate)->toBeTrue()
        ->and($reconciliation->notes)->toMatchArray([
            'force_majeure' => [
                'overlap_minutes' => 45,
                'reason' => 'Plant Stoppage',
            ],
        ]);
});

it('does not flag when overlap is below 15 minutes', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:10:00',
        'duration_minutes' => 10,
    ]);

    $outcome = $this->action->handle();

    $reconciliation->refresh();
    expect($outcome->candidates)->toHaveCount(0)
        ->and($reconciliation->dispute_candidate)->toBeFalse();
});

it('only considers DEM heads', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'PLO',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
    ]);

    $this->action->handle();

    expect($reconciliation->refresh()->dispute_candidate)->toBeFalse();
});

it('is idempotent — re-running does not duplicate notes', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $first = $this->action->handle();
    $second = $this->action->handle();

    expect($first->candidates)->toHaveCount(1)
        ->and($second->candidates)->toHaveCount(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StitchForceMajeureDisputesActionTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the action**

Create `app/Actions/StitchForceMajeureDisputesAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StitchForceMajeureDisputesAction
{
    private const MIN_OVERLAP_MINUTES = 15;

    public function handle(int $lookbackDays = 30): ForceMajeureStitchOutcome
    {
        $candidates = [];
        $rakesScanned = 0;
        $eventsConsidered = 0;

        DB::transaction(function () use ($lookbackDays, &$candidates, &$rakesScanned, &$eventsConsidered): void {
            $reconciliations = PenaltyReconciliation::query()
                ->where('penalty_code', 'DEM')
                ->where('dispute_candidate', false)
                ->whereNull('notes->force_majeure')
                ->where('reconciled_at', '>=', now()->subDays($lookbackDays))
                ->with(['rake:id,siding_id,placement_time,loading_end_time'])
                ->get();

            $rakesScanned = $reconciliations->count();

            foreach ($reconciliations as $reconciliation) {
                $rake = $reconciliation->rake;

                if ($rake === null || $rake->placement_time === null || $rake->loading_end_time === null) {
                    continue;
                }

                $events = LoadriteDowntimeEvent::query()
                    ->where('siding_id', $rake->siding_id)
                    ->where('start_local_time', '<', $rake->loading_end_time)
                    ->where(function ($q) use ($rake): void {
                        $q->whereNull('end_local_time')
                            ->orWhere('end_local_time', '>', $rake->placement_time);
                    })
                    ->get();

                $eventsConsidered += $events->count();

                $totalOverlap = 0;
                $reasons = [];
                $eventIds = [];

                foreach ($events as $event) {
                    $start = max($event->start_local_time->getTimestamp(), $rake->placement_time->getTimestamp());
                    $end = min(
                        ($event->end_local_time ?? now())->getTimestamp(),
                        $rake->loading_end_time->getTimestamp(),
                    );
                    $overlapSeconds = $end - $start;

                    if ($overlapSeconds <= 0) {
                        continue;
                    }

                    $totalOverlap += (int) floor($overlapSeconds / 60);

                    if ($event->reason_name !== null && ! in_array($event->reason_name, $reasons, true)) {
                        $reasons[] = $event->reason_name;
                    }

                    $eventIds[] = $event->id;
                }

                if ($totalOverlap < self::MIN_OVERLAP_MINUTES) {
                    continue;
                }

                $reconciliation->update([
                    'dispute_candidate' => true,
                    'notes' => array_merge($reconciliation->notes ?? [], [
                        'force_majeure' => [
                            'overlap_minutes' => $totalOverlap,
                            'reason' => $reasons[0] ?? 'Loadrite downtime overlap',
                            'reasons_all' => $reasons,
                            'event_ids' => $eventIds,
                            'stitched_at' => now()->toIso8601String(),
                        ],
                    ]),
                ]);

                $candidates[] = [
                    'rake_id' => $rake->id,
                    'downtime_event_id' => $eventIds[0],
                    'overlap_minutes' => $totalOverlap,
                    'reason' => $reasons[0] ?? 'Loadrite downtime overlap',
                ];
            }
        });

        Log::info('penalty.force_majeure.stitched', [
            'rakes_scanned' => $rakesScanned,
            'events_considered' => $eventsConsidered,
            'candidates_flagged' => count($candidates),
        ]);

        return new ForceMajeureStitchOutcome($candidates, $rakesScanned, $eventsConsidered);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StitchForceMajeureDisputesActionTest --compact`. Expected: PASS, 4 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Actions/StitchForceMajeureDisputesAction.php tests/Unit/Actions/StitchForceMajeureDisputesActionTest.php
git commit -m "feat(penalty): stitch Loadrite downtime to DEM dispute candidates"
```

---

### Task 10: Artisan command `disputes:stitch-force-majeure`

**Files:**
- Create: `app/Console/Commands/StitchForceMajeureDisputesCommand.php`
- Test: `tests/Feature/Console/StitchForceMajeureDisputesCommandTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;

it('runs the stitcher and reports candidate count', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:30:00',
        'duration_minutes' => 30,
    ]);

    $this->artisan('disputes:stitch-force-majeure')
        ->expectsOutputToContain('Flagged 1 dispute candidate')
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StitchForceMajeureDisputesCommandTest --compact`. Expected: FAIL.

- [ ] **Step 3: Create the command**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\StitchForceMajeureDisputesAction;
use Illuminate\Console\Command;

final class StitchForceMajeureDisputesCommand extends Command
{
    protected $signature = 'disputes:stitch-force-majeure
                            {--lookback=30 : Days of reconciliations to scan}';

    protected $description = 'Cross-reference Loadrite downtime events with DEM reconciliations and flag force-majeure dispute candidates.';

    public function handle(StitchForceMajeureDisputesAction $action): int
    {
        $lookback = (int) $this->option('lookback');
        $outcome = $action->handle($lookback);

        $count = count($outcome->candidates);
        $this->line("Scanned {$outcome->rakesScanned} reconciliations, considered {$outcome->downtimeEventsConsidered} downtime events.");
        $this->info("Flagged {$count} dispute candidate(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StitchForceMajeureDisputesCommandTest --compact`. Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/StitchForceMajeureDisputesCommand.php \
        tests/Feature/Console/StitchForceMajeureDisputesCommandTest.php
git commit -m "feat(penalty): add disputes:stitch-force-majeure command"
```

---

### Task 11: `loadrite:fetch-downtime` command + schedule both

**Files:**
- Create: `app/Console/Commands/LoadriteFetchDowntimeCommand.php`
- Modify: project's schedule file

- [ ] **Step 1: Inspect where scheduling currently lives**

Run: `grep -RIn "schedule->" app/Console routes/console.php 2>/dev/null | head -20`
Note which file declares the schedule.

- [ ] **Step 2: Create the wrapper command**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchLoadriteDowntimeJob;
use App\Models\LoadriteSetting;
use Illuminate\Console\Command;

final class LoadriteFetchDowntimeCommand extends Command
{
    protected $signature = 'loadrite:fetch-downtime
                            {--lookback=7 : Days of downtime to fetch}';

    protected $description = 'Fetch Loadrite downtime events for every configured siding into the local cache table.';

    public function handle(): int
    {
        $settings = LoadriteSetting::query()->whereNotNull('siding_id')->get();

        if ($settings->isEmpty()) {
            $this->warn('No loadrite_settings rows found. Nothing to fetch.');

            return self::SUCCESS;
        }

        $lookback = (int) $this->option('lookback');

        foreach ($settings as $setting) {
            FetchLoadriteDowntimeJob::dispatch($setting->siding_id, $lookback)
                ->onQueue('loadrite-poll');
            $this->line("Dispatched fetch for siding_id={$setting->siding_id}");
        }

        $this->info("Dispatched {$settings->count()} fetch job(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Register both schedules**

In the file identified at Step 1, add inside the schedule closure (or schedule binding):

```php
$schedule->command('loadrite:fetch-downtime')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('loadrite-fetch-downtime');

$schedule->command('disputes:stitch-force-majeure --lookback=30')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('disputes-stitch-force-majeure');
```

- [ ] **Step 4: Verify schedule registered**

Run: `php artisan schedule:list`
Expected output contains both new entries.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/LoadriteFetchDowntimeCommand.php \
        app/Console/Kernel.php routes/console.php 2>/dev/null
git commit -m "feat(loadrite): schedule downtime fetch + force-majeure stitch"
```

---

### Task 12: Documentation + manifest

**Files:**
- Create: `docs/developer/backend/actions/StitchForceMajeureDisputesAction.md`
- Modify: `docs/.manifest.json`

- [ ] **Step 1: Write the action doc**

```markdown
# StitchForceMajeureDisputesAction

**Path:** `app/Actions/StitchForceMajeureDisputesAction.php`

**Purpose:** Cross-reference Loadrite `loadrite_downtime_events` with DEM reconciliations in `penalty_reconciliations` and flag `dispute_candidate = true` when total overlap with a rake's loading window exceeds 15 minutes.

## Invocation

| Caller | Frequency |
|---|---|
| `disputes:stitch-force-majeure` artisan command | Nightly via scheduler |
| Manual replay | Engineer-triggered when downtime corpus is back-filled |

## Inputs

`handle(int $lookbackDays = 30): ForceMajeureStitchOutcome`

## Outputs

`ForceMajeureStitchOutcome` with `candidates`, `rakesScanned`, `downtimeEventsConsidered`.

## Side effects

For each flagged reconciliation:
- `dispute_candidate = true`
- `notes->force_majeure` JSON populated with overlap minutes, reason, event ids, stitched_at timestamp.

## Idempotency

Reconciliations already carrying `notes->force_majeure` are skipped.

## Telemetry

Structured log at level `info`, tag `penalty.force_majeure.stitched`.
```

- [ ] **Step 2: Run docs sync**

Run: `php artisan docs:sync`
Run: `php artisan docs:sync --check`. Expected: passes.

- [ ] **Step 3: Commit**

```bash
git add docs/developer/backend/actions/StitchForceMajeureDisputesAction.md docs/.manifest.json
git commit -m "docs: document StitchForceMajeureDisputesAction"
```

---

### Task 13: Pint format pass

- [ ] **Step 1: Run Pint on changed PHP files**

Run: `vendor/bin/pint --dirty --format agent`. Commit any changes.

```bash
git add -A && git commit -m "style: pint fixes" 2>/dev/null || true
```

---

### Task 14: Final test sweep

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`. Expected: green, no regressions.

- [ ] **Step 2: Sanity-check schedule output**

Run: `php artisan schedule:list | grep -E "loadrite|disputes"`. Expected: both new entries present.

---

## Self-Review

- **Spec coverage:** Four endpoints scaffolded (Tasks 1–4), Downtime fully wired through to dispute flagging (Tasks 5–11), docs updated (Task 12). Conveyor / Haul / Positions request classes are deliberately scaffolded-only for this plan; future stitchers reuse them. ✅
- **Placeholder scan:** No TBD/TODO/"add validation". ✅
- **Type consistency:** `ForceMajeureStitchOutcome` properties (`candidates`, `rakesScanned`, `downtimeEventsConsidered`) match across DTO, action, command. ✅

---

## Execution Notes

- **Activation:** automatic — once a siding has a `loadrite_settings` row, both the daily fetch and the nightly stitch start producing data. No flag flip.
- **Rollback:** `git revert` + `php artisan migrate:rollback --step=1`. The cache table empties; reconciliations carrying `notes->force_majeure` retain the JSON harmlessly.
- **Operational dependency:** an active `loadrite_settings` row per siding intended to be evidence-eligible. Pakur and Kurwa have no row today.
