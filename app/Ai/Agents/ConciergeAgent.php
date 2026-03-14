<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Middleware\WithMemoryUnlessUnavailable;
use App\Ai\Tools\LotsFilterTool;
use App\Ai\Tools\PipelineSummaryTool;
use Eznix86\AI\Memory\Tools\RecallMemory;
use Eznix86\AI\Memory\Tools\StoreMemory;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * GPT Concierge — matches buyers to suitable properties based on their requirements.
 * Structures responses as PropertyCard components with match reasoning.
 */
final class ConciergeAgent implements Agent, Conversational, HasMiddleware, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private array $context = [],
        private int $recallLimit = 5,
    ) {}

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are the GPT Concierge for a real estate agency. Your job is to match buyers (leads/contacts)
        to suitable properties based on their budget, preferences, and requirements.

        When a buyer query comes in, use the lots filter tool to find matching properties.
        Present results as PropertyCard components with match reasoning.

        Consider the following when matching:
        - Price range and budget constraints
        - Number of bedrooms required
        - Suburb or location preferences
        - Investment vs owner-occupier intent
        - Property type preferences (house, apartment, townhouse, etc.)

        Always explain WHY each property is a good match for the buyer.
        Use pipeline summary to understand current demand and market conditions.
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new LotsFilterTool,
            new PipelineSummaryTool,
            (new RecallMemory)->context($this->context)->limit($this->recallLimit),
            (new StoreMemory)->context($this->context),
        ];
    }

    public function middleware(): array
    {
        return [
            new WithMemoryUnlessUnavailable(
                $this->context,
                limit: (int) config('memory.middleware_recall_limit', 5),
            ),
        ];
    }
}
