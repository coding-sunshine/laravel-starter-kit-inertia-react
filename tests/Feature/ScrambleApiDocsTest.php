<?php

declare(strict_types=1);

use Tests\TestCase;

it('returns api info at GET /api', function (): void {
    /** @var TestCase $test */
    $test = $this;
    $response = $test->getJson('/api');

    $response->assertOk()
        ->assertJsonStructure(['name', 'version', 'message']);
});

it('serves api documentation at /docs/api in local environment', function (): void {
    /** @var TestCase $test */
    $test = $this;
    if (! app()->isLocal()) {
        $test->markTestSkipped('Scramble docs are only available in local environment');
    }

    $response = $test->get('/docs/api');

    $response->assertOk();
});

it('serves openapi json at /docs/api.json in local environment', function (): void {
    /** @var TestCase $test */
    $test = $this;
    if (! app()->isLocal()) {
        $test->markTestSkipped('Scramble docs are only available in local environment');
    }

    $response = $test->get('/docs/api.json');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['openapi', 'info', 'paths']);
});
