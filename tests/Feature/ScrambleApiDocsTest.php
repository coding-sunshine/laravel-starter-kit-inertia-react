<?php

declare(strict_types=1);

it('returns api info at GET /api', function (): void {
    $response = $this->getJson('/api');

    $response->assertOk()
        ->assertJsonStructure(['name', 'version', 'message']);
});

it('serves api documentation at /docs/api in local environment', function (): void {
    if (app()->environment() !== 'local') {
        $this->markTestSkipped('Scramble docs are only available in local environment');
    }

    $response = $this->get('/docs/api');

    $response->assertOk();
});

it('serves openapi json at /docs/api.json in local environment', function (): void {
    if (app()->environment() !== 'local') {
        $this->markTestSkipped('Scramble docs are only available in local environment');
    }

    $response = $this->get('/docs/api.json');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['openapi', 'info', 'paths']);
});
