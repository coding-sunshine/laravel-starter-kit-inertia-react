<?php

declare(strict_types=1);

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

it('returns connector without refresh when token is valid', function (): void {
    LoadriteSetting::factory()->create([
        'siding_id' => null,
        'access_token' => 'valid-token',
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addHour(),
    ]);

    $connector = app(LoadriteTokenManager::class)->getConnector(null);
    expect($connector)->toBeInstanceOf(LoadriteConnector::class);
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
            'tokenType' => 'Bearer',
            'accessTokenExpiryInSeconds' => 3600,
            'refreshTokenExpiryInSeconds' => 86400,
        ], 200),
    ]);

    app(LoadriteTokenManager::class)->getConnector(null);

    $setting->refresh();
    expect($setting->access_token)->toBe('new-token');
    expect($setting->refresh_token)->toBe('new-refresh');
});

it('stores new tokens via store method', function (): void {
    $result = app(LoadriteTokenManager::class)->store(
        null,
        'Dumka',
        'access-123',
        'refresh-123',
        now()->addHour()
    );

    expect($result)->toBeInstanceOf(LoadriteSetting::class);
    expect(LoadriteSetting::where('siding_id', null)->count())->toBe(1);
    expect($result->site_name)->toBe('Dumka');
});
