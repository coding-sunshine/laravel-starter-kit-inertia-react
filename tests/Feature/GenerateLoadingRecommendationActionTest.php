<?php

declare(strict_types=1);

use App\Actions\GenerateLoadingRecommendationAction;
use App\Models\Rake;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;

it('returns null when prism is unavailable', function (): void {
    $prism = Mockery::mock(PrismService::class);
    $prism->shouldReceive('isAvailable')->once()->andReturn(false);

    $rake = Rake::factory()->create();

    $action = new GenerateLoadingRecommendationAction($prism);
    $result = $action->handle($rake);

    expect($result)->toBeNull();
});

it('returns cached result on second call', function (): void {
    $rake = Rake::factory()->create();
    $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

    Cache::put($cacheKey, 'Cached recommendation text', 21600);

    $prism = Mockery::mock(PrismService::class);
    $prism->shouldNotReceive('isAvailable');

    $action = new GenerateLoadingRecommendationAction($prism);
    $result = $action->handle($rake);

    expect($result)->toBe('Cached recommendation text');
});

it('returns null when cached value is unavailable sentinel', function (): void {
    $rake = Rake::factory()->create();
    $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$rake->siding_id}";

    Cache::put($cacheKey, '__unavailable__', 21600);

    $prism = Mockery::mock(PrismService::class);
    $prism->shouldNotReceive('isAvailable');

    $action = new GenerateLoadingRecommendationAction($prism);

    expect($action->handle($rake))->toBeNull();
});

it('returns null when rake has no siding', function (): void {
    $rake = Rake::factory()->create(['siding_id' => null]);

    $prism = Mockery::mock(PrismService::class);
    $prism->shouldNotReceive('isAvailable');

    $action = new GenerateLoadingRecommendationAction($prism);

    expect($action->handle($rake))->toBeNull();
});
