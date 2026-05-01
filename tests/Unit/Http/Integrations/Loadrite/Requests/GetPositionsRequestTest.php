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
