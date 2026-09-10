<?php

declare(strict_types=1);

use App\Actions\ApplyDemurragePenaltyAction;
use App\Events\AppliedPenaltyPersisted;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\SectionTimer;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'default_rate' => 225, 'is_active' => true]);
    SectionTimer::factory()->create(['section_name' => 'loading', 'free_minutes' => 300]);
});

it('emits AppliedPenaltyPersisted with source=demurrage after demurrage applies', function (): void {
    Event::fake([AppliedPenaltyPersisted::class]);

    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);

    resolve(ApplyDemurragePenaltyAction::class)->handle($rake);

    Event::assertDispatched(AppliedPenaltyPersisted::class, function ($e) use ($rake): bool {
        return $e->rake->is($rake) && $e->source === 'demurrage';
    });
});
