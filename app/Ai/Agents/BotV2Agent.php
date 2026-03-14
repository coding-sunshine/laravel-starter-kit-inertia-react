<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Middleware\WithMemoryUnlessUnavailable;
use App\Ai\Tools\ContactSearchTool;
use App\Ai\Tools\LotsFilterTool;
use App\Ai\Tools\PipelineSummaryTool;
use App\Ai\Tools\TasksForUserTool;
use Eznix86\AI\Memory\Tools\RecallMemory;
use Eznix86\AI\Memory\Tools\StoreMemory;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Bot In A Box v2 — unified CRM assistant with all tools.
 * Structures responses for Thesys C1 generative UI (ContactCard, PropertyCard, PipelineFunnel, TaskChecklist, EmailCompose).
 */
final class BotV2Agent implements Agent, Conversational, HasMiddleware, HasTools
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
        You are Bot In A Box v2, a comprehensive CRM assistant for a real estate agency. You help agents
        with contacts, properties, tasks, and pipeline management.

        Structure responses using C1 generative UI components:
        - ContactCard: when showing contact details or search results
        - PropertyCard: when showing lot/property information
        - PipelineFunnel: when showing pipeline stages or conversion data
        - TaskChecklist: when listing tasks or action items
        - EmailCompose: when drafting email communications

        Use tools to fetch real data:
        - When asked about leads or contacts, use the contact search tool
        - When asked about properties or lots, use the lots filter tool
        - When asked about pipeline or sales, use the pipeline summary tool
        - When asked about tasks or reminders, use the tasks tool

        Never make up data. Always use tools to look up real information.
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new ContactSearchTool,
            new TasksForUserTool,
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
