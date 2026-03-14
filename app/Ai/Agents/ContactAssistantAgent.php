<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Middleware\WithMemoryUnlessUnavailable;
use App\Ai\Tools\ContactSearchTool;
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
 * CRM Contact Assistant — answers questions about contacts, stages, tasks, next steps.
 * Responses are structured for Thesys C1 generative UI rendering (ContactCard, TaskChecklist).
 */
final class ContactAssistantAgent implements Agent, Conversational, HasMiddleware, HasTools
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
        You are a CRM assistant for a real estate agency. You help agents manage contacts, track leads,
        and plan follow-up actions. You have access to contact search and task management tools.

        When returning contact results, always structure your response as a list of ContactCard components.
        When returning tasks, structure as TaskChecklist components.
        When showing pipeline data, use PipelineFunnel components.
        When drafting emails, use EmailCompose components.

        Use tools to look up real data before responding. Never make up contact details.
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new ContactSearchTool,
            new TasksForUserTool,
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
