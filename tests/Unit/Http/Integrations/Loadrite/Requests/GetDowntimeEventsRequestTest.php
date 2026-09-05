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
