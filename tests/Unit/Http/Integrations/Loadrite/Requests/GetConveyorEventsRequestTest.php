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
