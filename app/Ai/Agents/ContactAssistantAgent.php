<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Middleware\WithMemoryUnlessUnavailable;
use App\Ai\Tools\ContactsIndex;
use App\Models\EmbeddingDocument;
use Eznix86\AI\Memory\Tools\RecallMemory;
use Eznix86\AI\Memory\Tools\StoreMemory;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;

/**
 * Agent for contact-related questions: search and list contacts in the current organization.
 * Use forUser($user) for a new conversation or continue($conversationId, $user) to continue.
 * Pass context (e.g. ['user_id' => $user->id]) so memories are scoped per user.
 */
final class ContactAssistantAgent implements Agent, Conversational, HasMiddleware, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private array $context = [],
        private int $recallLimit = 10,
    ) {}

    public function instructions(): string
    {
        return 'You are a helpful contact assistant. You can search and list contacts in the current organization. '
            .'Use the "contacts_index" tool to find contacts by name, company, or email, or to list recently updated contacts. '
            .'Use the "similarity_search" tool to find relevant contact or CRM content by meaning (e.g. "contacts interested in SMSF", "notes about finance"). '
            .'Summarize results clearly. You can store and recall information from previous conversations using memory tools.';
    }

    public function tools(): iterable
    {
        $ragTool = SimilaritySearch::usingModel(
            EmbeddingDocument::class,
            'embedding',
            0.6,
            15,
        )->withDescription('Search CRM content (contacts, notes, summaries) by semantic similarity. Use for questions like "contacts interested in X" or "notes about Y".');

        return [
            new ContactsIndex,
            $ragTool,
            (new RecallMemory)->context($this->context)->limit($this->recallLimit),
            (new StoreMemory)->context($this->context),
        ];
    }

    public function middleware(): array
    {
        return [
            new WithMemoryUnlessUnavailable($this->context, limit: (int) config('memory.middleware_recall_limit', 5)),
        ];
    }
}
