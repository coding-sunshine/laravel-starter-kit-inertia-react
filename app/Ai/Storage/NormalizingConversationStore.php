<?php

declare(strict_types=1);

namespace App\Ai\Storage;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Wraps the default conversation store and ensures message content is never null
 * when loading history, so OpenRouter (and other APIs that require string content) do not reject the request.
 */
final class NormalizingConversationStore implements ConversationStore
{
    public function __construct(
        private readonly ConversationStore $store,
    ) {}

    public function latestConversationId(string|int $userId): ?string
    {
        return $this->store->latestConversationId($userId);
    }

    public function storeConversation(string|int|null $userId, string $title): string
    {
        return $this->store->storeConversation($userId, $title);
    }

    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        return $this->store->storeUserMessage($conversationId, $userId, $prompt);
    }

    public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
    {
        return $this->store->storeAssistantMessage($conversationId, $userId, $prompt, $response);
    }

    public function getLatestConversationMessages(string $conversationId, int $limit): Collection
    {
        return $this->store->getLatestConversationMessages($conversationId, $limit)
            ->map(fn (Message $m) => new Message($m->role, $m->content === null ? '' : $m->content))
            ->filter(function (Message $m): bool {
                // OpenRouter rejects assistant messages with null or missing content. Drop empty assistant turns.
                if ($m->role === MessageRole::Assistant && ($m->content === null || trim((string) $m->content) === '')) {
                    return false;
                }
                return true;
            })
            ->values();
    }
}
