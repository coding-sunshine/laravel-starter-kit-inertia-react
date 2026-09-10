<?php

declare(strict_types=1);

use App\Actions\BuildManagerBrief;
use App\DataTransferObjects\ManagerBrief\Payload;
use App\Models\Siding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a minimal Payload for a given siding.
 */
function makeTestPayload(int $sidingId): Payload
{
    return new Payload(
        actions: [],
        generatedAt: CarbonImmutable::now(),
        sidingId: $sidingId,
        modelUsed: 'openai/gpt-4o-mini',
        aiStatus: 'ok',
        failedReason: null,
    );
}

/**
 * Bind a fake BuildManagerBrief into the container that always succeeds.
 *
 * Because BuildManagerBrief is final, we cannot mock or subclass it.
 * We bind an anonymous fake object to the same key so app(BuildManagerBrief::class)
 * returns our fake without any constructor injection of the real dependencies.
 */
function bindSuccessFake(): void
{
    app()->bind(BuildManagerBrief::class, fn (): object => new class()
    {
        public function handle(int $sidingId): Payload
        {
            return makeTestPayload($sidingId);
        }
    });
}

/**
 * Bind a fake that throws for $failSidingId and succeeds for all others.
 */
function bindPartialFailFake(int $failSidingId): void
{
    app()->bind(BuildManagerBrief::class, fn (): object => new class($failSidingId)
    {
        public function __construct(private readonly int $failSidingId) {}

        public function handle(int $sidingId): Payload
        {
            if ($sidingId === $this->failSidingId) {
                throw new RuntimeException("Simulated failure for siding {$sidingId}");
            }

            return makeTestPayload($sidingId);
        }
    });
}

// ---------------------------------------------------------------------------
// 12.1 — Writes payload to cache per siding (both sidings populated)
// ---------------------------------------------------------------------------

it('writes payload to cache per siding', function (): void {
    $siding1 = Siding::factory()->create();
    $siding2 = Siding::factory()->create();

    $sid1 = (int) $siding1->id;
    $sid2 = (int) $siding2->id;

    bindSuccessFake();

    $this->artisan('manager-brief:refresh')->assertSuccessful();

    expect(Cache::has("manager-brief:{$sid1}:v1"))->toBeTrue();
    expect(Cache::has("manager-brief:{$sid2}:v1"))->toBeTrue();

    // Verify round-trip: cached array can be rehydrated to a Payload
    $cached1 = Cache::get("manager-brief:{$sid1}:v1");
    $cached2 = Cache::get("manager-brief:{$sid2}:v1");

    expect($cached1)->toBeArray();
    expect($cached2)->toBeArray();

    $payload1 = Payload::fromArray($cached1);
    $payload2 = Payload::fromArray($cached2);

    expect($payload1)->toBeInstanceOf(Payload::class);
    expect($payload1->sidingId)->toBe($sid1);
    expect($payload2)->toBeInstanceOf(Payload::class);
    expect($payload2->sidingId)->toBe($sid2);
});

// ---------------------------------------------------------------------------
// 12.2 — Iterates all active (non-deleted) sidings when no --siding flag
// ---------------------------------------------------------------------------

it('iterates all active sidings when no siding flag given', function (): void {
    $siding1 = Siding::factory()->create();
    $siding2 = Siding::factory()->create();

    $sid1 = (int) $siding1->id;
    $sid2 = (int) $siding2->id;

    bindSuccessFake();

    $this->artisan('manager-brief:refresh')->assertSuccessful();

    expect(Cache::has("manager-brief:{$sid1}:v1"))->toBeTrue();
    expect(Cache::has("manager-brief:{$sid2}:v1"))->toBeTrue();
});

// ---------------------------------------------------------------------------
// 12.3 — Processes only the given siding when --siding flag is set
// ---------------------------------------------------------------------------

it('processes only the given siding when flag set', function (): void {
    $siding1 = Siding::factory()->create();
    $siding2 = Siding::factory()->create();

    $sid1 = (int) $siding1->id;
    $sid2 = (int) $siding2->id;

    bindSuccessFake();

    $this->artisan('manager-brief:refresh', ['--siding' => $sid2])->assertSuccessful();

    expect(Cache::has("manager-brief:{$sid2}:v1"))->toBeTrue();
    expect(Cache::has("manager-brief:{$sid1}:v1"))->toBeFalse();
});

// ---------------------------------------------------------------------------
// 12.4 — Logs warning and continues when one siding fails
// ---------------------------------------------------------------------------

it('logs warning and continues when one siding fails', function (): void {
    $siding1 = Siding::factory()->create();
    $siding2 = Siding::factory()->create();

    $sid1 = (int) $siding1->id;
    $sid2 = (int) $siding2->id;

    Log::spy();

    bindPartialFailFake($sid1);

    $this->artisan('manager-brief:refresh')->assertSuccessful();

    // Siding 1 failed — cache key must NOT be set
    expect(Cache::has("manager-brief:{$sid1}:v1"))->toBeFalse();

    // Siding 2 succeeded — cache key MUST be set
    expect(Cache::has("manager-brief:{$sid2}:v1"))->toBeTrue();

    // A WARNING was logged for siding 1 including its siding_id
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context = []): bool => isset($context['siding_id']) && $context['siding_id'] === $sid1);
});
