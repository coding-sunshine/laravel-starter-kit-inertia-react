<?php

declare(strict_types=1);

use App\Ai\Agents\ManagerBriefAgent;
use App\DataTransferObjects\ManagerBrief\ActionCard;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Services\PrismService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

/**
 * Build a minimal Signal DTO for testing.
 *
 * @param  array{type?: string, severity?: string, rs_at_stake?: float, recency_minutes?: int, actionability?: float, payload?: array<string, mixed>}  $overrides
 */
function makeAgentSignal(array $overrides = []): Signal
{
    return new Signal(
        type: $overrides['type'] ?? 'overload_exposure',
        severity: $overrides['severity'] ?? 'high',
        rsAtStake: $overrides['rs_at_stake'] ?? 50.0,
        recencyMinutes: $overrides['recency_minutes'] ?? 30,
        actionability: $overrides['actionability'] ?? 0.9,
        payload: $overrides['payload'] ?? [],
    );
}

/**
 * Build a valid action-card array matching the wire schema.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function makeCardArray(array $overrides = []): array
{
    return array_merge([
        'severity' => 'high',
        'title' => 'Test action card',
        'why' => 'Because this is important.',
        'rs_at_stake' => 120.5,
        'deep_link' => '/dashboard',
        'deadline' => null,
    ], $overrides);
}

/**
 * Build a fake TextResponse whose `text` property contains the given JSON string.
 */
function makeFakeTextResponse(string $json): TextResponse
{
    return new TextResponse(
        steps: new Collection,
        text: $json,
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(promptTokens: 10, completionTokens: 20),
        meta: new Meta(id: 'fake-id', model: 'fake-model'),
        messages: new Collection,
    );
}

it('sends all signals to prism and returns 5 typed action cards', function (): void {
    $signals = [
        makeAgentSignal(['type' => 'force_majeure', 'severity' => 'critical', 'rs_at_stake' => 200.0]),
        makeAgentSignal(['type' => 'operator_anomaly', 'severity' => 'high', 'rs_at_stake' => 150.0]),
        makeAgentSignal(['type' => 'demurrage_risk', 'severity' => 'medium', 'rs_at_stake' => 80.0]),
        makeAgentSignal(['type' => 'overload_exposure', 'severity' => 'high', 'rs_at_stake' => 60.0]),
        makeAgentSignal(['type' => 'scale_silence', 'severity' => 'low', 'rs_at_stake' => 10.0]),
    ];

    $cards = [
        makeCardArray(['severity' => 'critical', 'title' => 'Resolve force majeure', 'deep_link' => '/disputes']),
        makeCardArray(['severity' => 'high', 'title' => 'Operator anomaly action', 'deep_link' => '/dashboard?section=loader-overload&operator=John']),
        makeCardArray(['severity' => 'medium', 'title' => 'Demurrage deadline', 'deep_link' => '/control-panel-2']),
        makeCardArray(['severity' => 'high', 'title' => 'Overload exposure mitigation', 'deep_link' => '/dashboard']),
        makeCardArray(['severity' => 'low', 'title' => 'Scale reconnect required', 'deep_link' => '/dashboard']),
    ];

    $json = json_encode($cards);

    $fake = Prism::fake([makeFakeTextResponse($json)]);

    $agent = new ManagerBriefAgent(new PrismService);
    $result = $agent->synthesise($signals);

    // Assert that Prism was called exactly once
    $fake->assertCallCount(1);

    // Assert prompt contains all signal types
    $fake->assertRequest(function (array $requests): void {
        $prompt = $requests[0]->prompt();
        expect($prompt)
            ->toContain('force_majeure')
            ->toContain('operator_anomaly')
            ->toContain('demurrage_risk')
            ->toContain('overload_exposure')
            ->toContain('scale_silence');
    });

    // Assert result is list<ActionCard> of length 5
    expect($result)->toBeArray()->toHaveCount(5);
    expect($result[0])->toBeInstanceOf(ActionCard::class);
    expect($result[0]->severity)->toBe('critical');
    expect($result[1]->severity)->toBe('high');
    expect($result[2]->severity)->toBe('medium');
});

it('returns null and logs warning when prism throws', function (): void {
    $signals = [
        makeAgentSignal(),
    ];

    // Provide no responses so PrismFake::text() will return empty text,
    // then we verify the JSON parse failure causes a warning.
    // Actually: we need Prism to throw. We can do this by using an empty fake
    // and the agent will get empty text → json_decode returns null → throw RuntimeException.
    // But let's make it cleaner by providing a response that can't be decoded as an array.
    $fake = Prism::fake([makeFakeTextResponse('not valid json at all {{{')]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'manager-brief: AI synthesis failed'
                && isset($context['exception'])
                && isset($context['signal_count'])
                && $context['signal_count'] === 1;
        });

    $agent = new ManagerBriefAgent(new PrismService);
    $result = $agent->synthesise($signals);

    expect($result)->toBeNull();

    $fake->assertCallCount(1);
});

it('drops cards failing schema validation', function (): void {
    $signals = [
        makeAgentSignal(['type' => 'overload_exposure']),
        makeAgentSignal(['type' => 'force_majeure']),
    ];

    $cards = [
        // Valid
        makeCardArray(['severity' => 'high', 'title' => 'Valid card 1', 'deep_link' => '/dashboard']),
        // Invalid: severity not in allowed set
        makeCardArray(['severity' => 'urgent', 'title' => 'Invalid severity card', 'deep_link' => '/dashboard']),
        // Valid
        makeCardArray(['severity' => 'medium', 'title' => 'Valid card 2', 'deep_link' => '/disputes']),
        // Invalid: title is empty
        makeCardArray(['severity' => 'low', 'title' => '', 'deep_link' => '/dashboard']),
        // Invalid: deep_link does not start with /
        makeCardArray(['severity' => 'low', 'title' => 'Bad deep link', 'deep_link' => 'http://external.com']),
    ];

    $json = json_encode($cards);

    Prism::fake([makeFakeTextResponse($json)]);

    $agent = new ManagerBriefAgent(new PrismService);
    $result = $agent->synthesise($signals);

    expect($result)->toBeArray()->toHaveCount(2);
    expect($result[0]->severity)->toBe('high');
    expect($result[1]->severity)->toBe('medium');
});

it('clamps output to 5 cards even if model returns more', function (): void {
    $signals = [
        makeAgentSignal(),
        makeAgentSignal(),
    ];

    // LLM returns 7 valid cards
    $cards = array_map(
        fn (int $i): array => makeCardArray(['title' => "Card {$i}", 'severity' => 'low']),
        range(1, 7)
    );

    $json = json_encode($cards);

    Prism::fake([makeFakeTextResponse($json)]);

    $agent = new ManagerBriefAgent(new PrismService);
    $result = $agent->synthesise($signals);

    expect($result)->toBeArray()->toHaveCount(5);
    expect($result[0]->title)->toBe('Card 1');
    expect($result[4]->title)->toBe('Card 5');
});
