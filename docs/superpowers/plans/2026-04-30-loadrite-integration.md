# Loadrite API Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Poll the Loadrite InsightHQ REST API every 30 seconds per active siding, sync per-bucket weight events into `WagonLoading` records, and fire real-time overload alerts (90% CC = warning, 100%+ CC = critical) via Reverb, database notifications, and SMS/push.

**Architecture:** Saloon connector under `App\Http\Integrations\Loadrite\`. A self-scheduling `PollLoadriteJob` runs via Horizon, dispatches `SyncLoadriteWeightJob` and `EvaluateOverloadAlertJob` per event. API tokens stored encrypted in `loadrite_settings` table, auto-refreshed on 401 via `LoadriteTokenManager`. Redis stores polling cursor (last event timestamp) and alert debounce keys.

**Tech Stack:** Laravel 13, Saloon, Horizon 5, Reverb 1, Redis, Pest 4

---

### Task 1: Migration — `loadrite_settings` table

**Files:**
- Create: `database/migrations/2026_04_30_000002_create_loadrite_settings_table.php`
- Create: `app/Models/LoadriteSetting.php`

- [ ] **Step 1: Generate migration and model**

```bash
php artisan make:migration create_loadrite_settings_table --no-interaction
php artisan make:model LoadriteSetting --no-interaction
```

- [ ] **Step 2: Fill in the migration**

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
        Schema::create('loadrite_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->nullable()->constrained('sidings')->nullOnDelete();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadrite_settings');
    }
};
```

- [ ] **Step 3: Fill in the model**

Replace `app/Models/LoadriteSetting.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoadriteSetting extends Model
{
    protected $fillable = [
        'siding_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate --no-interaction
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/LoadriteSetting.php
git commit -m "feat: add loadrite_settings table and LoadriteSetting model"
```

---

### Task 2: Migration — add Loadrite columns to `wagon_loading`

**Files:**
- Create: `database/migrations/2026_04_30_000003_add_loadrite_columns_to_wagon_loading_table.php`

- [ ] **Step 1: Generate migration**

```bash
php artisan make:migration add_loadrite_columns_to_wagon_loading_table --no-interaction
```

- [ ] **Step 2: Fill in the migration**

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
        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->decimal('loadrite_weight_mt', 8, 3)->nullable()->after('loaded_quantity_mt');
            $table->enum('weight_source', ['manual', 'loadrite', 'weighbridge'])->default('manual')->after('loadrite_weight_mt');
            $table->dateTime('loadrite_last_synced_at')->nullable()->after('weight_source');
            $table->boolean('loadrite_override')->default(false)->after('loadrite_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('wagon_loading', function (Blueprint $table): void {
            $table->dropColumn(['loadrite_weight_mt', 'weight_source', 'loadrite_last_synced_at', 'loadrite_override']);
        });
    }
};
```

- [ ] **Step 3: Update `WagonLoading` model fillable and casts**

In `app/Models/WagonLoading.php`, add to `$fillable` array:

```php
'loadrite_weight_mt',
'weight_source',
'loadrite_last_synced_at',
'loadrite_override',
```

Add to `$casts` array:

```php
'loadrite_weight_mt' => 'decimal:3',
'loadrite_last_synced_at' => 'datetime',
'loadrite_override' => 'boolean',
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate --no-interaction
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/WagonLoading.php
git commit -m "feat: add loadrite_weight_mt, weight_source, loadrite_last_synced_at, loadrite_override to wagon_loading"
```

---

### Task 3: Saloon connector and requests

**Files:**
- Create: `app/Http/Integrations/Loadrite/LoadriteConnector.php`
- Create: `app/Http/Integrations/Loadrite/Requests/RefreshTokenRequest.php`
- Create: `app/Http/Integrations/Loadrite/Requests/GetNewWeightEventsRequest.php`
- Create: `app/Http/Integrations/Loadrite/Requests/GetLoadingEventsRequest.php`
- Create: `app/Http/Integrations/Loadrite/Requests/GetContextRequest.php`

- [ ] **Step 1: Create the connector**

```bash
mkdir -p app/Http/Integrations/Loadrite/Requests
```

Create `app/Http/Integrations/Loadrite/LoadriteConnector.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

final class LoadriteConnector extends Connector
{
    use AcceptsJson;

    public function __construct(private readonly string $accessToken) {}

    public function resolveBaseUrl(): string
    {
        return 'https://apicloud.loadrite-myinsighthq.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
```

- [ ] **Step 2: Create `RefreshTokenRequest`**

Create `app/Http/Integrations/Loadrite/Requests/RefreshTokenRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class RefreshTokenRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(private readonly string $refreshToken) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/auth/refresh-token';
    }

    protected function defaultBody(): array
    {
        return ['refreshToken' => $this->refreshToken];
    }
}
```

- [ ] **Step 3: Create `GetNewWeightEventsRequest`**

Create `app/Http/Integrations/Loadrite/Requests/GetNewWeightEventsRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetNewWeightEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $from) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/NewWeight';
    }

    protected function defaultQuery(): array
    {
        return ['from' => $this->from];
    }
}
```

- [ ] **Step 4: Create `GetLoadingEventsRequest`**

Create `app/Http/Integrations/Loadrite/Requests/GetLoadingEventsRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetLoadingEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $from) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Loading';
    }

    protected function defaultQuery(): array
    {
        return ['from' => $this->from];
    }
}
```

- [ ] **Step 5: Create `GetContextRequest`**

Create `app/Http/Integrations/Loadrite/Requests/GetContextRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetContextRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/api/v2/context';
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Integrations/Loadrite/
git commit -m "feat: add Loadrite Saloon connector and requests"
```

---

### Task 4: `LoadriteTokenManager` service

**Files:**
- Create: `app/Services/LoadriteTokenManager.php`

- [ ] **Step 1: Create the service**

Create `app/Services/LoadriteTokenManager.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\RefreshTokenRequest;
use App\Models\LoadriteSetting;
use Illuminate\Support\Carbon;

final class LoadriteTokenManager
{
    public function getConnector(?int $sidingId = null): LoadriteConnector
    {
        $setting = $this->getOrFail($sidingId);

        if ($this->isExpired($setting)) {
            $setting = $this->refresh($setting);
        }

        return new LoadriteConnector($setting->access_token);
    }

    public function refresh(LoadriteSetting $setting): LoadriteSetting
    {
        $connector = new LoadriteConnector($setting->access_token);
        $response = $connector->send(new RefreshTokenRequest($setting->refresh_token));

        $data = $response->json();

        $setting->update([
            'access_token' => $data['accessToken'],
            'refresh_token' => $data['refreshToken'],
            'expires_at' => Carbon::parse($data['expiresAt']),
        ]);

        return $setting->fresh();
    }

    public function store(?int $sidingId, string $accessToken, string $refreshToken, Carbon $expiresAt): LoadriteSetting
    {
        return LoadriteSetting::updateOrCreate(
            ['siding_id' => $sidingId],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
            ],
        );
    }

    private function getOrFail(?int $sidingId): LoadriteSetting
    {
        return LoadriteSetting::query()
            ->where('siding_id', $sidingId)
            ->firstOrFail();
    }

    private function isExpired(LoadriteSetting $setting): bool
    {
        return $setting->expires_at->isPast();
    }
}
```

- [ ] **Step 2: Write unit tests**

```bash
php artisan make:test --pest --unit LoadriteTokenManagerTest --no-interaction
```

Replace `tests/Unit/LoadriteTokenManagerTest.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('returns connector without refresh when token is valid', function (): void {
    LoadriteSetting::factory()->create([
        'siding_id' => null,
        'access_token' => 'valid-token',
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addHour(),
    ]);

    MockClient::global([]);

    $connector = app(LoadriteTokenManager::class)->getConnector(null);
    expect($connector)->toBeInstanceOf(LoadriteConnector::class);
    // No refresh call made — MockClient would throw if any request was sent
});

it('refreshes token when expired and stores new tokens', function (): void {
    $setting = LoadriteSetting::factory()->create([
        'siding_id' => null,
        'access_token' => 'old-token',
        'refresh_token' => 'old-refresh',
        'expires_at' => now()->subMinute(),
    ]);

    MockClient::global([
        MockResponse::make([
            'accessToken' => 'new-token',
            'refreshToken' => 'new-refresh',
            'expiresAt' => now()->addHour()->toIso8601String(),
        ], 200),
    ]);

    app(LoadriteTokenManager::class)->getConnector(null);

    $setting->refresh();
    expect($setting->access_token)->toBe('new-token');
    expect($setting->refresh_token)->toBe('new-refresh');
});
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --compact --filter=LoadriteTokenManagerTest
```

Expected: all PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Services/LoadriteTokenManager.php tests/Unit/LoadriteTokenManagerTest.php
git commit -m "feat: add LoadriteTokenManager service with auto-refresh on expired token"
```

---

### Task 5: `PollLoadriteJob`

**Files:**
- Create: `app/Jobs/PollLoadriteJob.php`

- [ ] **Step 1: Create the job**

```bash
php artisan make:job PollLoadriteJob --no-interaction
```

Replace `app/Jobs/PollLoadriteJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Integrations\Loadrite\Requests\GetNewWeightEventsRequest;
use App\Services\LoadriteTokenManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class PollLoadriteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 25;

    public function __construct(private readonly int $sidingId) {}

    public function handle(LoadriteTokenManager $tokenManager): void
    {
        $lockKey = "loadrite:polling:{$this->sidingId}";
        $cursorKey = "loadrite:cursor:{$this->sidingId}";

        $lock = Cache::lock($lockKey, 35);

        if (! $lock->get()) {
            return;
        }

        try {
            $from = Cache::get($cursorKey, now()->subHour()->toIso8601String());
            $connector = $tokenManager->getConnector($this->sidingId);
            $response = $connector->send(new GetNewWeightEventsRequest($from));

            if (! $response->successful()) {
                Log::warning('Loadrite poll failed', [
                    'siding_id' => $this->sidingId,
                    'status' => $response->status(),
                ]);

                return;
            }

            $events = $response->json() ?? [];
            $lastTimestamp = $from;

            foreach ($events as $event) {
                SyncLoadriteWeightJob::dispatch($event, $this->sidingId)->onQueue('loadrite-sync');
                EvaluateOverloadAlertJob::dispatch($event, $this->sidingId)->onQueue('loadrite-alerts');

                if (isset($event['Timestamp']) && $event['Timestamp'] > $lastTimestamp) {
                    $lastTimestamp = $event['Timestamp'];
                }
            }

            Cache::put($cursorKey, $lastTimestamp, now()->addHours(24));
        } finally {
            $lock->release();
        }

        self::dispatch($this->sidingId)
            ->onQueue('loadrite-poll')
            ->delay(now()->addSeconds(30));
    }
}
```

- [ ] **Step 2: Write feature test**

```bash
php artisan make:test --pest PollLoadriteJobTest --no-interaction
```

Replace `tests/Feature/PollLoadriteJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Jobs\EvaluateOverloadAlertJob;
use App\Jobs\PollLoadriteJob;
use App\Jobs\SyncLoadriteWeightJob;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    LoadriteSetting::factory()->create([
        'siding_id' => 1,
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHour(),
    ]);
});

it('dispatches child jobs per event and updates cursor', function (): void {
    Bus::fake([SyncLoadriteWeightJob::class, EvaluateOverloadAlertJob::class, PollLoadriteJob::class]);

    MockClient::global([
        MockResponse::make([
            ['Sequence' => 1, 'Timestamp' => '2026-04-30T10:00:00Z', 'Weight' => 45.2],
            ['Sequence' => 2, 'Timestamp' => '2026-04-30T10:05:00Z', 'Weight' => 60.1],
        ], 200),
    ]);

    (new PollLoadriteJob(1))->handle(app(LoadriteTokenManager::class));

    Bus::assertDispatched(SyncLoadriteWeightJob::class, 2);
    Bus::assertDispatched(EvaluateOverloadAlertJob::class, 2);
    Bus::assertDispatched(PollLoadriteJob::class);

    expect(Cache::get('loadrite:cursor:1'))->toBe('2026-04-30T10:05:00Z');
});

it('exits immediately if Redis lock is already held', function (): void {
    Bus::fake();

    Cache::lock('loadrite:polling:1', 35)->get();

    (new PollLoadriteJob(1))->handle(app(LoadriteTokenManager::class));

    Bus::assertNothingDispatched();
});
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --compact --filter=PollLoadriteJobTest
```

Expected: all PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/PollLoadriteJob.php tests/Feature/PollLoadriteJobTest.php
git commit -m "feat: add PollLoadriteJob — self-scheduling Horizon job polling Loadrite every 30s"
```

---

### Task 6: `SyncLoadriteWeightJob`

**Files:**
- Create: `app/Jobs/SyncLoadriteWeightJob.php`

- [ ] **Step 1: Create the job**

```bash
php artisan make:job SyncLoadriteWeightJob --no-interaction
```

Replace `app/Jobs/SyncLoadriteWeightJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Rake;
use App\Models\WagonLoading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncLoadriteWeightJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array{Sequence: int, Weight: float, Timestamp: string}  $event
     */
    public function __construct(
        private readonly array $event,
        private readonly int $sidingId,
    ) {}

    public function handle(): void
    {
        $rake = Rake::query()
            ->where('siding_id', $this->sidingId)
            ->whereIn('status', ['loading', 'placed'])
            ->latest('placement_time')
            ->first();

        if (! $rake) {
            Log::warning('Loadrite sync: no active rake at siding', [
                'siding_id' => $this->sidingId,
                'event' => $this->event,
            ]);

            return;
        }

        $wagonLoading = WagonLoading::query()
            ->where('rake_id', $rake->id)
            ->whereHas('wagon', fn ($q) => $q->where('wagon_number', $this->event['Sequence']))
            ->first();

        if (! $wagonLoading) {
            Log::warning('Loadrite sync: no matching WagonLoading for sequence', [
                'rake_id' => $rake->id,
                'sequence' => $this->event['Sequence'],
            ]);

            return;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return;
        }

        $updates = [
            'loadrite_weight_mt' => $this->event['Weight'],
            'loadrite_last_synced_at' => now(),
        ];

        if (! $wagonLoading->loadrite_override) {
            $updates['weight_source'] = 'loadrite';
        }

        $wagonLoading->update($updates);
    }
}
```

- [ ] **Step 2: Write feature test**

```bash
php artisan make:test --pest SyncLoadriteWeightJobTest --no-interaction
```

Replace `tests/Feature/SyncLoadriteWeightJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Jobs\SyncLoadriteWeightJob;
use App\Models\Rake;
use App\Models\Wagon;
use App\Models\WagonLoading;

it('updates loadrite_weight_mt and sets weight_source to loadrite for manual records', function (): void {
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 5]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 5, 'Weight' => 62.4, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    $loading->refresh();
    expect((float) $loading->loadrite_weight_mt)->toBe(62.4);
    expect($loading->weight_source)->toBe('loadrite');
});

it('does not overwrite weighbridge records', function (): void {
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 3]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 67.0,
        'weight_source' => 'weighbridge',
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 3, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    $loading->refresh();
    expect($loading->weight_source)->toBe('weighbridge');
    expect($loading->loadrite_weight_mt)->toBeNull();
});

it('logs warning and skips when no matching wagon loading found', function (): void {
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);

    \Illuminate\Support\Facades\Log::shouldReceive('warning')->once();

    (new SyncLoadriteWeightJob(['Sequence' => 99, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], 1))->handle();
});
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --compact --filter=SyncLoadriteWeightJobTest
```

Expected: all PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/SyncLoadriteWeightJob.php tests/Feature/SyncLoadriteWeightJobTest.php
git commit -m "feat: add SyncLoadriteWeightJob — sync per-bucket weights into WagonLoading"
```

---

### Task 7: Broadcast events and `LoadriteOverloadNotification`

**Files:**
- Create: `app/Events/WagonOverloadWarning.php`
- Create: `app/Events/WagonOverloadCritical.php`
- Create: `app/Notifications/LoadriteOverloadNotification.php`

- [ ] **Step 1: Create `WagonOverloadWarning`**

Create `app/Events/WagonOverloadWarning.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WagonOverloadWarning implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $wagonId,
        public readonly string $wagonNumber,
        public readonly float $weightMt,
        public readonly float $ccMt,
        public readonly float $percentage,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'wagon.overload.warning';
    }

    public function broadcastWith(): array
    {
        return [
            'wagon_id' => $this->wagonId,
            'wagon_number' => $this->wagonNumber,
            'weight_mt' => $this->weightMt,
            'cc_mt' => $this->ccMt,
            'percentage' => $this->percentage,
            'level' => 'warning',
        ];
    }
}
```

- [ ] **Step 2: Create `WagonOverloadCritical`**

Create `app/Events/WagonOverloadCritical.php` — identical structure, change class name and `broadcastAs()` to `'wagon.overload.critical'` and `level` to `'critical'`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WagonOverloadCritical implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sidingId,
        public readonly int $wagonId,
        public readonly string $wagonNumber,
        public readonly float $weightMt,
        public readonly float $ccMt,
        public readonly float $percentage,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('siding.'.$this->sidingId)];
    }

    public function broadcastAs(): string
    {
        return 'wagon.overload.critical';
    }

    public function broadcastWith(): array
    {
        return [
            'wagon_id' => $this->wagonId,
            'wagon_number' => $this->wagonNumber,
            'weight_mt' => $this->weightMt,
            'cc_mt' => $this->ccMt,
            'percentage' => $this->percentage,
            'level' => 'critical',
        ];
    }
}
```

- [ ] **Step 3: Create `LoadriteOverloadNotification`**

Create `app/Notifications/LoadriteOverloadNotification.php`:

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class LoadriteOverloadNotification extends Notification
{
    public function __construct(
        private readonly string $level,
        private readonly int $wagonId,
        private readonly string $wagonNumber,
        private readonly int $sidingId,
        private readonly float $weightMt,
        private readonly float $ccMt,
        private readonly float $percentage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->level === 'warning' ? 'overload_warning' : 'overload_critical',
            'wagon_id' => $this->wagonId,
            'wagon_number' => $this->wagonNumber,
            'siding_id' => $this->sidingId,
            'weight_mt' => $this->weightMt,
            'cc_mt' => $this->ccMt,
            'percentage' => $this->percentage,
            'level' => $this->level,
            'source' => 'loadrite',
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Events/WagonOverloadWarning.php app/Events/WagonOverloadCritical.php app/Notifications/LoadriteOverloadNotification.php
git commit -m "feat: add WagonOverloadWarning, WagonOverloadCritical events and LoadriteOverloadNotification"
```

---

### Task 8: `EvaluateOverloadAlertJob`

**Files:**
- Create: `app/Jobs/EvaluateOverloadAlertJob.php`

- [ ] **Step 1: Create the job**

```bash
php artisan make:job EvaluateOverloadAlertJob --no-interaction
```

Replace `app/Jobs/EvaluateOverloadAlertJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WagonOverloadCritical;
use App\Events\WagonOverloadWarning;
use App\Models\User;
use App\Models\Wagon;
use App\Models\WagonLoading;
use App\Notifications\LoadriteOverloadNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class EvaluateOverloadAlertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array{Sequence: int, Weight: float, Timestamp: string}  $event
     */
    public function __construct(
        private readonly array $event,
        private readonly int $sidingId,
    ) {}

    public function handle(): void
    {
        $wagonLoading = WagonLoading::query()
            ->with('wagon')
            ->whereHas('wagon', fn ($q) => $q->where('wagon_number', $this->event['Sequence']))
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $this->sidingId)->whereIn('status', ['loading', 'placed']))
            ->first();

        if (! $wagonLoading || ! $wagonLoading->wagon) {
            return;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return;
        }

        $ccMt = (float) ($wagonLoading->cc_capacity_mt ?? $wagonLoading->wagon->cc_mt ?? 0);

        if ($ccMt <= 0) {
            Log::warning('Loadrite alert: zero CC for wagon', ['sequence' => $this->event['Sequence']]);

            return;
        }

        $percentage = ($this->event['Weight'] / $ccMt) * 100;
        $wagonId = $wagonLoading->wagon->id;
        $wagonNumber = (string) $this->event['Sequence'];

        if ($percentage >= 100) {
            $this->fireAlert('critical', $wagonId, $wagonNumber, $ccMt, $percentage);
        } elseif ($percentage >= 90) {
            $this->fireAlert('warning', $wagonId, $wagonNumber, $ccMt, $percentage);
        }
    }

    private function fireAlert(string $level, int $wagonId, string $wagonNumber, float $ccMt, float $percentage): void
    {
        $debounceKey = "loadrite:alert:{$wagonId}:{$level}";

        if (Cache::has($debounceKey)) {
            return;
        }

        Cache::put($debounceKey, true, now()->addMinutes(5));

        $weightMt = (float) $this->event['Weight'];

        if ($level === 'warning') {
            WagonOverloadWarning::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $ccMt, $percentage);
        } else {
            WagonOverloadCritical::dispatch($this->sidingId, $wagonId, $wagonNumber, $weightMt, $ccMt, $percentage);
        }

        $notification = new LoadriteOverloadNotification($level, $wagonId, $wagonNumber, $this->sidingId, $weightMt, $ccMt, $percentage);
        Notification::send(User::query()->get(), $notification);
    }
}
```

- [ ] **Step 2: Write unit tests**

```bash
php artisan make:test --pest --unit EvaluateOverloadAlertJobTest --no-interaction
```

Replace `tests/Unit/EvaluateOverloadAlertJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Events\WagonOverloadCritical;
use App\Events\WagonOverloadWarning;
use App\Jobs\EvaluateOverloadAlertJob;
use App\Models\Rake;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;

it('fires no alert below 90% CC', function (): void {
    Event::fake();
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 1, 'cc_mt' => 68]);
    WagonLoading::factory()->create(['rake_id' => $rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    (new EvaluateOverloadAlertJob(['Sequence' => 1, 'Weight' => 60.0, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertNotDispatched(WagonOverloadWarning::class);
    Event::assertNotDispatched(WagonOverloadCritical::class);
});

it('fires warning alert at exactly 90% CC and sets debounce key', function (): void {
    Event::fake();
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 2, 'cc_mt' => 68]);
    WagonLoading::factory()->create(['rake_id' => $rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    (new EvaluateOverloadAlertJob(['Sequence' => 2, 'Weight' => 61.2, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertDispatched(WagonOverloadWarning::class);
    expect(Cache::has("loadrite:alert:{$wagon->id}:warning"))->toBeTrue();
});

it('does not fire duplicate alert within 5-minute debounce window', function (): void {
    Event::fake();
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 3, 'cc_mt' => 68]);
    WagonLoading::factory()->create(['rake_id' => $rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);
    Cache::put("loadrite:alert:{$wagon->id}:warning", true, now()->addMinutes(5));

    (new EvaluateOverloadAlertJob(['Sequence' => 3, 'Weight' => 61.2, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertNotDispatched(WagonOverloadWarning::class);
});

it('fires critical alert at 100%+ CC', function (): void {
    Event::fake();
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 4, 'cc_mt' => 68]);
    WagonLoading::factory()->create(['rake_id' => $rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    (new EvaluateOverloadAlertJob(['Sequence' => 4, 'Weight' => 68.1, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertDispatched(WagonOverloadCritical::class);
});

it('skips weighbridge records', function (): void {
    Event::fake();
    $rake = Rake::factory()->create(['siding_id' => 1, 'status' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 5, 'cc_mt' => 68]);
    WagonLoading::factory()->create(['rake_id' => $rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'weighbridge']);

    (new EvaluateOverloadAlertJob(['Sequence' => 5, 'Weight' => 70.0, 'Timestamp' => now()->toIso8601String()], 1))->handle();

    Event::assertNotDispatched(WagonOverloadCritical::class);
});
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --compact --filter=EvaluateOverloadAlertJobTest
```

Expected: all PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/EvaluateOverloadAlertJob.php tests/Unit/EvaluateOverloadAlertJobTest.php
git commit -m "feat: add EvaluateOverloadAlertJob with 90%/100% thresholds and 5-min debounce"
```

---

### Task 9: Artisan commands — `loadrite:start-polling` and `loadrite:store-token`

**Files:**
- Create: `app/Console/Commands/LoadriteStartPolling.php`
- Create: `app/Console/Commands/LoadriteStoreToken.php`
- Modify: `routes/console.php` (scheduler registration)

- [ ] **Step 1: Create `loadrite:start-polling`**

```bash
php artisan make:command LoadriteStartPolling --no-interaction
```

Replace `app/Console/Commands/LoadriteStartPolling.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PollLoadriteJob;
use App\Models\LoadriteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class LoadriteStartPolling extends Command
{
    protected $signature = 'loadrite:start-polling';

    protected $description = 'Ensure PollLoadriteJob is running for each configured siding. No-op if already polling.';

    public function handle(): int
    {
        $settings = LoadriteSetting::query()->get();

        if ($settings->isEmpty()) {
            $this->warn('No Loadrite settings found. Run loadrite:store-token first.');

            return Command::SUCCESS;
        }

        foreach ($settings as $setting) {
            $lockKey = 'loadrite:polling:'.($setting->siding_id ?? 'global');

            if (Cache::has($lockKey)) {
                $this->info("Siding {$setting->siding_id}: already polling, skipping.");
                continue;
            }

            PollLoadriteJob::dispatch($setting->siding_id ?? 0)->onQueue('loadrite-poll');
            $this->info("Siding {$setting->siding_id}: dispatched PollLoadriteJob.");
        }

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 2: Create `loadrite:store-token`**

```bash
php artisan make:command LoadriteStoreToken --no-interaction
```

Replace `app/Console/Commands/LoadriteStoreToken.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LoadriteTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class LoadriteStoreToken extends Command
{
    protected $signature = 'loadrite:store-token
                            {--siding= : Siding ID (leave blank for global token)}';

    protected $description = 'Interactively store an encrypted Loadrite API token.';

    public function handle(LoadriteTokenManager $manager): int
    {
        $sidingId = $this->option('siding') ? (int) $this->option('siding') : null;

        $accessToken = $this->secret('Paste the Loadrite access token:');
        $refreshToken = $this->secret('Paste the Loadrite refresh token:');
        $expiresAt = Carbon::parse($this->ask('Token expiry datetime (ISO8601):'));

        $manager->store($sidingId, $accessToken, $refreshToken, $expiresAt);

        $this->info('Token stored and encrypted successfully.');

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 3: Register scheduler**

Open `routes/console.php` and add inside the `Schedule` closure:

```php
Schedule::command('loadrite:start-polling')->everyFiveMinutes();
```

- [ ] **Step 4: Add queues to Horizon config**

Open `config/horizon.php` and add to both `production` and `local` supervisor-1:

```php
'queue' => ['loadrite-poll', 'loadrite-sync', 'loadrite-alerts', 'default'],
```

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/LoadriteStartPolling.php app/Console/Commands/LoadriteStoreToken.php routes/console.php config/horizon.php
git commit -m "feat: add loadrite:start-polling watchdog and loadrite:store-token commands, register scheduler"
```

---

### Task 10: Full test suite pass

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --compact
```

Expected: all PASS.

- [ ] **Step 2: Run pint on all new files**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "style: pint formatting fixes for Loadrite integration"
```
