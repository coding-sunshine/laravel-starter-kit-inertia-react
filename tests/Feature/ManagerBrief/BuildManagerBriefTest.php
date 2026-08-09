<?php

declare(strict_types=1);

use App\Actions\BuildManagerBrief;
use App\DataTransferObjects\ManagerBrief\ActionCard;
use App\DataTransferObjects\ManagerBrief\Payload;
use App\Models\Siding;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;
use Tests\Helpers\SeedsManagerBriefFixture;

uses(SeedsManagerBriefFixture::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a fake TextResponse with controlled JSON content.
 */
function makeBriefFakeResponse(string $json, string $model = 'openai/gpt-4o-mini'): TextResponse
{
    return new TextResponse(
        steps: new Collection,
        text: $json,
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(promptTokens: 50, completionTokens: 100),
        meta: new Meta(id: 'fake-id', model: $model),
        messages: new Collection,
    );
}

/**
 * Build a valid action-card JSON payload.
 *
 * @param  int  $count  Number of cards to generate (up to 5)
 * @return string JSON array
 */
function makeValidCardsJson(int $count = 3): string
{
    $cards = [];

    for ($i = 1; $i <= $count; $i++) {
        $cards[] = [
            'severity' => 'high',
            'title' => "Action card {$i}",
            'why' => "Because signal {$i} needs attention.",
            'rs_at_stake' => 1000.0 * $i,
            'deep_link' => '/dashboard',
            'deadline' => null,
        ];
    }

    return json_encode($cards);
}

/**
 * Stub a DowntimePenaltyMatcher that returns no candidates (no force_majeure signals).
 */
function makeEmptyMatcher(): DowntimePenaltyMatcherContract
{
    return new class implements DowntimePenaltyMatcherContract
    {
        public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array
        {
            return [];
        }
    };
}

// ---------------------------------------------------------------------------
// 11.1 — Happy-path: collects signals, ranks them, calls agent, returns payload
// ---------------------------------------------------------------------------

it('collects signals ranks them calls agent and returns payload', function (): void {
    // Arrange: seed fixture + stub matcher
    $siding = Siding::factory()->create(['name' => 'Test Siding Alpha']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());

    $this->seedManagerBriefFixture($sidingId);

    $json = makeValidCardsJson(3);
    $fake = Prism::fake([makeBriefFakeResponse($json)]);

    // Act
    $orchestrator = app(BuildManagerBrief::class);
    $payload = $orchestrator->handle($sidingId);

    // Assert: returns a Payload DTO
    expect($payload)->toBeInstanceOf(Payload::class);

    // Assert: actions are hydrated as ActionCard instances
    expect($payload->actions)->toBeArray();
    expect($payload->actions)->toHaveCount(3);
    expect($payload->actions[0])->toBeInstanceOf(ActionCard::class);

    // Assert: ai_status is 'ok' on success
    expect($payload->aiStatus)->toBe('ok');
    expect($payload->failedReason)->toBeNull();

    // Assert: siding_id matches
    expect($payload->sidingId)->toBe($sidingId);

    // Assert: Prism was invoked (signals were forwarded to agent)
    $fake->assertCallCount(1);
});

// ---------------------------------------------------------------------------
// 11.2 — Failure path: agent returns null → payload has ai_status = 'failed'
// ---------------------------------------------------------------------------

it('writes payload with ai_status failed when agent returns null', function (): void {
    // Arrange: seed a minimal siding (no fixture needed for failure case)
    $siding = Siding::factory()->create(['name' => 'Failure Siding']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());

    // Malformed JSON → agent's parseAndValidate throws → synthesise returns null
    Prism::fake([makeBriefFakeResponse('not valid json {{{')]);

    // Act
    $orchestrator = app(BuildManagerBrief::class);
    $payload = $orchestrator->handle($sidingId);

    // Assert: Payload reflects the failure
    expect($payload)->toBeInstanceOf(Payload::class);
    expect($payload->aiStatus)->toBe('failed');
    expect($payload->failedReason)->toBe('agent_returned_null');
    expect($payload->actions)->toBeArray()->toHaveCount(0);
    expect($payload->modelUsed)->toBeNull();

    // siding_id is still populated
    expect($payload->sidingId)->toBe($sidingId);
});

// ---------------------------------------------------------------------------
// 11.3 — Payload fields: generated_at, model_used, siding_id are all populated
// ---------------------------------------------------------------------------

it('includes generated_at and model_used and siding_id in payload', function (): void {
    // Arrange
    $siding = Siding::factory()->create(['name' => 'Fields Check Siding']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());

    $json = makeValidCardsJson(1);
    Prism::fake([makeBriefFakeResponse($json, 'openai/gpt-4o-mini')]);

    $before = CarbonImmutable::now();

    // Act
    $orchestrator = app(BuildManagerBrief::class);
    $payload = $orchestrator->handle($sidingId);

    $after = CarbonImmutable::now();

    // Assert: generated_at is a CarbonImmutable within the test window
    expect($payload->generatedAt)->toBeInstanceOf(CarbonImmutable::class);
    expect($payload->generatedAt->timestamp)->toBeGreaterThanOrEqual($before->timestamp);
    expect($payload->generatedAt->timestamp)->toBeLessThanOrEqual($after->timestamp);

    // Assert: siding_id is correct
    expect($payload->sidingId)->toBe($sidingId);

    // Assert: model_used is a non-empty string (defaults to config fast_model)
    expect($payload->modelUsed)->toBeString();
    expect($payload->modelUsed)->not->toBeEmpty();

    // Assert: ai_status is ok on a valid response
    expect($payload->aiStatus)->toBe('ok');
    expect($payload->failedReason)->toBeNull();
});

// ---------------------------------------------------------------------------
// Quota guards: signal-hash reuse + daily auto-request budget
// ---------------------------------------------------------------------------

it('reuses cached cards without a second LLM call when signals are unchanged', function (): void {
    $siding = Siding::factory()->create(['name' => 'Reuse Siding']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());
    $this->seedManagerBriefFixture($sidingId);

    $fake = Prism::fake([makeBriefFakeResponse(makeValidCardsJson(2))]);

    $orchestrator = app(BuildManagerBrief::class);
    $first = $orchestrator->handle($sidingId);
    $second = $orchestrator->handle($sidingId);

    // Only one LLM call despite two runs — second served from the hash cache.
    $fake->assertCallCount(1);
    expect($first->aiStatus)->toBe('ok');
    expect($second->aiStatus)->toBe('ok');
    expect($second->actions)->toHaveCount(2);
});

it('serves last known cards without calling the LLM when the daily budget is exhausted', function (): void {
    config()->set('ai.daily_auto_request_limit', 1);

    $siding = Siding::factory()->create(['name' => 'Budget Siding']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());
    $this->seedManagerBriefFixture($sidingId);

    $fake = Prism::fake([makeBriefFakeResponse(makeValidCardsJson(1))]);

    // Pre-spend today's entire budget and seed a last-known synthesis.
    Cache::put('ai:auto-requests:'.CarbonImmutable::now()->format('Y-m-d'), 1, 3600);
    $staleCard = new ActionCard(
        severity: 'high',
        title: 'Stale but served',
        why: 'Cached from an earlier successful run.',
        rsAtStake: 100.0,
        deepLink: '/dashboard',
        deadline: null,
    );
    Cache::put("manager-brief:last-cards:{$sidingId}", [$staleCard], 3600);

    $payload = app(BuildManagerBrief::class)->handle($sidingId);

    $fake->assertCallCount(0);
    expect($payload->aiStatus)->toBe('ok');
    expect($payload->actions)->toHaveCount(1);
    expect($payload->actions[0]->title)->toBe('Stale but served');
});

it('fails without calling the LLM when budget is exhausted and no last cards exist', function (): void {
    config()->set('ai.daily_auto_request_limit', 1);

    $siding = Siding::factory()->create(['name' => 'Budget Siding 2']);
    $sidingId = (int) $siding->id;

    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeEmptyMatcher());
    $this->seedManagerBriefFixture($sidingId);

    $fake = Prism::fake([makeBriefFakeResponse(makeValidCardsJson(1))]);

    Cache::put('ai:auto-requests:'.CarbonImmutable::now()->format('Y-m-d'), 1, 3600);

    $payload = app(BuildManagerBrief::class)->handle($sidingId);

    $fake->assertCallCount(0);
    expect($payload->aiStatus)->toBe('failed');
});
