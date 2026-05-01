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
