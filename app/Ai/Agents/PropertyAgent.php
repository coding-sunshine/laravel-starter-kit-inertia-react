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
 * Property & Sales Agent — answers questions about projects, lots, reservations, and pipeline.
 * Responses are structured for Thesys C1 generative UI (PropertyCard, PipelineFunnel, CommissionTable).
 */
final class PropertyAgent implements Agent, Conversational, HasMiddleware, HasTools
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
        You are a property and sales assistant for a real estate agency. You help agents find matching
        lots for buyers, track the reservation pipeline, and analyze commission data.

        When returning lot/project results, structure as PropertyCard components.
        When showing pipeline stages, use PipelineFunnel components.
        When showing commission data, use CommissionTable components.

        Use tools to look up real data. Focus on matching buyer budgets and preferences to available lots.
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
