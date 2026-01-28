<?php

declare(strict_types=1);

use App\Services\PrismService;
use Prism\Prism\Enums\Provider;

test('prism service can be instantiated', function () {
    $service = new PrismService;

    expect($service)->toBeInstanceOf(PrismService::class);
});

test('prism service can create text request', function () {
    $service = new PrismService;
    $request = $service->text('openai/gpt-4o-mini');

    expect($request)->toBeInstanceOf(Prism\Prism\Text\PendingRequest::class);
});

test('prism service can create text request with default model', function () {
    $service = new PrismService;
    $request = $service->text();

    expect($request)->toBeInstanceOf(Prism\Prism\Text\PendingRequest::class);
});

test('prism service can create structured request', function () {
    $service = new PrismService;
    $request = $service->structured();

    expect($request)->toBeInstanceOf(Prism\Prism\Structured\PendingRequest::class);
});

test('prism service can get default provider and model', function () {
    $service = new PrismService;

    expect($service->defaultProvider())->toBeInstanceOf(Provider::class);
    expect($service->defaultModel())->toBeString();
});

test('prism service can use different providers', function () {
    $service = new PrismService;
    $request = $service->using(Provider::OpenRouter, 'openai/gpt-4o-mini');

    expect($request)->toBeInstanceOf(Prism\Prism\Text\PendingRequest::class);
});

test('ai helper function returns PrismService instance', function () {
    $service = ai();

    expect($service)->toBeInstanceOf(PrismService::class);
});
