<?php

declare(strict_types=1);

use App\Events\WagonOverloadCritical;
use App\Events\WagonOverloadWarning;
use App\Jobs\EvaluateOverloadAlertJob;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $siding = Siding::factory()->create();
    $this->siding = $siding;
    $this->rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
});

it('fires no alert below 90% CC', function (): void {
    Event::fake();
    $wagon = Wagon::factory()->create(['wagon_number' => 1]);
    WagonLoading::factory()->create(['rake_id' => $this->rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    (new EvaluateOverloadAlertJob(['Sequence' => 1, 'Weight' => 60.0, 'Timestamp' => now()->toIso8601String()], $this->siding->id))->handle();

    Event::assertNotDispatched(WagonOverloadWarning::class);
    Event::assertNotDispatched(WagonOverloadCritical::class);
});

it('fires warning alert at exactly 90% CC and sets debounce key', function (): void {
    Event::fake();
    $wagon = Wagon::factory()->create(['wagon_number' => 2]);
    WagonLoading::factory()->create(['rake_id' => $this->rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    // 61.2 / 68 = 90%
    (new EvaluateOverloadAlertJob(['Sequence' => 2, 'Weight' => 61.2, 'Timestamp' => now()->toIso8601String()], $this->siding->id))->handle();

    Event::assertDispatched(WagonOverloadWarning::class);
    expect(Cache::has("loadrite:alert:{$wagon->id}:warning"))->toBeTrue();
});

it('does not fire duplicate alert within 5-minute debounce window', function (): void {
    Event::fake();
    $wagon = Wagon::factory()->create(['wagon_number' => 3]);
    WagonLoading::factory()->create(['rake_id' => $this->rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);
    Cache::put("loadrite:alert:{$wagon->id}:warning", true, now()->addMinutes(5));

    (new EvaluateOverloadAlertJob(['Sequence' => 3, 'Weight' => 61.2, 'Timestamp' => now()->toIso8601String()], $this->siding->id))->handle();

    Event::assertNotDispatched(WagonOverloadWarning::class);
});

it('fires critical alert at 100%+ CC', function (): void {
    Event::fake();
    $wagon = Wagon::factory()->create(['wagon_number' => 4]);
    WagonLoading::factory()->create(['rake_id' => $this->rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'manual']);

    // 68.1 / 68 = 100.1%
    (new EvaluateOverloadAlertJob(['Sequence' => 4, 'Weight' => 68.1, 'Timestamp' => now()->toIso8601String()], $this->siding->id))->handle();

    Event::assertDispatched(WagonOverloadCritical::class);
});

it('skips weighbridge records', function (): void {
    Event::fake();
    $wagon = Wagon::factory()->create(['wagon_number' => 5]);
    WagonLoading::factory()->create(['rake_id' => $this->rake->id, 'wagon_id' => $wagon->id, 'cc_capacity_mt' => 68, 'weight_source' => 'weighbridge']);

    (new EvaluateOverloadAlertJob(['Sequence' => 5, 'Weight' => 70.0, 'Timestamp' => now()->toIso8601String()], $this->siding->id))->handle();

    Event::assertNotDispatched(WagonOverloadCritical::class);
    Event::assertNotDispatched(WagonOverloadWarning::class);
});
