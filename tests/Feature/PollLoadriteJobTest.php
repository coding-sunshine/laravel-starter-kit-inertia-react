<?php

declare(strict_types=1);

use App\Jobs\EvaluateOverloadAlertJob;
use App\Jobs\PollLoadriteJob;
use App\Jobs\SyncLoadriteWeightJob;
use App\Models\LoadriteSetting;
use App\Models\Siding;
use App\Services\LoadriteTokenManager;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

beforeEach(function (): void {
    $siding = Siding::factory()->create();

    LoadriteSetting::factory()->create([
        'siding_id' => $siding->id,
        'site_name' => 'Dumka',
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHour(),
    ]);

    $this->sidingId = $siding->id;
});

it('dispatches child jobs per event and updates cursor', function (): void {
    Bus::fake([SyncLoadriteWeightJob::class, EvaluateOverloadAlertJob::class, PollLoadriteJob::class]);

    // Events must be newer than the poll's FromLocalTime (now - lookback) for
    // the cursor to advance to them.
    $earlierTime = now()->subMinutes(20)->format('Y-m-d\TH:i:s\Z');
    $latestTime = now()->subMinutes(10)->format('Y-m-d\TH:i:s\Z');

    // The Loadrite API wraps events in a paginated { data, metaData } envelope.
    MockClient::global([
        MockResponse::make([
            'data' => [
                ['Id' => 'evt-1', 'Event' => 'Add', 'Weight' => 45.2, 'Time' => $earlierTime],
                ['Id' => 'evt-2', 'Event' => 'Short Total', 'Weight' => 60.1, 'Time' => $latestTime],
            ],
            'metaData' => ['numberOfPages' => 1],
        ], 200),
    ]);

    (new PollLoadriteJob($this->sidingId))->handle(app(LoadriteTokenManager::class));

    Bus::assertDispatched(SyncLoadriteWeightJob::class, 2);
    Bus::assertDispatched(EvaluateOverloadAlertJob::class, 2);
    Bus::assertDispatched(PollLoadriteJob::class);

    expect(Cache::get("loadrite:cursor:{$this->sidingId}"))->toBe($latestTime);
});

it('does not re-dispatch child jobs for events already stored', function (): void {
    Bus::fake([SyncLoadriteWeightJob::class, EvaluateOverloadAlertJob::class, PollLoadriteJob::class]);

    $earlierTime = now()->subMinutes(20)->format('Y-m-d\TH:i:s\Z');
    $latestTime = now()->subMinutes(10)->format('Y-m-d\TH:i:s\Z');

    // evt-1 was already ingested on a previous poll. The 6-hour lookback window
    // re-fetches it, but we must NOT re-dispatch its child jobs.
    DB::table('loadrite_events')->insert([
        'siding_id' => $this->sidingId,
        'event_id' => 'evt-1',
        'event_type' => 'Add',
        'event_time' => now()->subMinutes(20),
        'wagon_sequence' => 0,
        'weight_mt' => 45.2,
        'raw_payload' => json_encode(['Id' => 'evt-1']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    MockClient::global([
        MockResponse::make([
            'data' => [
                ['Id' => 'evt-1', 'Event' => 'Add', 'Weight' => 45.2, 'Time' => $earlierTime],
                ['Id' => 'evt-2', 'Event' => 'Short Total', 'Weight' => 60.1, 'Time' => $latestTime],
            ],
            'metaData' => ['numberOfPages' => 1],
        ], 200),
    ]);

    (new PollLoadriteJob($this->sidingId))->handle(app(LoadriteTokenManager::class));

    // Only evt-2 (new) should dispatch — not the already-stored evt-1.
    Bus::assertDispatched(SyncLoadriteWeightJob::class, 1);
    Bus::assertDispatched(EvaluateOverloadAlertJob::class, 1);
});

it('exits immediately if Redis lock is already held', function (): void {
    Bus::fake();

    Cache::lock("loadrite:polling:{$this->sidingId}", 35)->get();

    (new PollLoadriteJob($this->sidingId))->handle(app(LoadriteTokenManager::class));

    Bus::assertNothingDispatched();
});
