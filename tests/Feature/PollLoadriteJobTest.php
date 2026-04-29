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
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

beforeEach(function (): void {
    $siding = Siding::factory()->create();

    LoadriteSetting::factory()->create([
        'siding_id' => $siding->id,
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHour(),
    ]);

    $this->sidingId = $siding->id;
});

it('dispatches child jobs per event and updates cursor', function (): void {
    Bus::fake([SyncLoadriteWeightJob::class, EvaluateOverloadAlertJob::class, PollLoadriteJob::class]);

    MockClient::global([
        MockResponse::make([
            ['Sequence' => 1, 'Timestamp' => '2026-04-30T10:00:00Z', 'Weight' => 45.2],
            ['Sequence' => 2, 'Timestamp' => '2026-04-30T10:05:00Z', 'Weight' => 60.1],
        ], 200),
    ]);

    (new PollLoadriteJob($this->sidingId))->handle(app(LoadriteTokenManager::class));

    Bus::assertDispatched(SyncLoadriteWeightJob::class, 2);
    Bus::assertDispatched(EvaluateOverloadAlertJob::class, 2);
    Bus::assertDispatched(PollLoadriteJob::class);

    expect(Cache::get("loadrite:cursor:{$this->sidingId}"))->toBe('2026-04-30T10:05:00Z');
});

it('exits immediately if Redis lock is already held', function (): void {
    Bus::fake();

    Cache::lock("loadrite:polling:{$this->sidingId}", 35)->get();

    (new PollLoadriteJob($this->sidingId))->handle(app(LoadriteTokenManager::class));

    Bus::assertNothingDispatched();
});
