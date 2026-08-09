<?php

declare(strict_types=1);

use App\Actions\GenerateLoadingRecommendationAction;
use App\Models\Rake;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;

it('returns null when prism is unavailable', function (): void {
    config(['prism.providers.openrouter.api_key' => '']);

    $rake = Rake::factory()->create();

    $action = new GenerateLoadingRecommendationAction(new PrismService);
    $result = $action->handle($rake);

    expect($result)->toBeNull();
});

it('returns cached result on second call', function (): void {
    $rake = Rake::factory()->create();
    $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

    Cache::put($cacheKey, 'Cached recommendation text', 21600);

    $action = new GenerateLoadingRecommendationAction(new PrismService);
    $result = $action->handle($rake);

    expect($result)->toBe('Cached recommendation text');
});

it('returns null when cached value is unavailable sentinel', function (): void {
    $rake = Rake::factory()->create();
    $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

    Cache::put($cacheKey, '__unavailable__', 21600);

    $action = new GenerateLoadingRecommendationAction(new PrismService);

    expect($action->handle($rake))->toBeNull();
});

it('returns null when rake has no siding', function (): void {
    // Not persisted: the `rakes.siding_id` column is NOT NULL in the schema,
    // but handle() short-circuits on a null siding_id before touching the DB.
    $rake = Rake::factory()->make(['siding_id' => null]);

    $action = new GenerateLoadingRecommendationAction(new PrismService);

    expect($action->handle($rake))->toBeNull();
});
