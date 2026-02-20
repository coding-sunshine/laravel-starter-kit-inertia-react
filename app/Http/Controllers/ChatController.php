<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\ChatbotAgent;
use App\Ai\ChatContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\ConversationStore;
use Throwable;

final class ChatController extends Controller
{
    public function __construct(
        private readonly ConversationStore $conversationStore,
        private readonly ChatContextBuilder $contextBuilder,
    ) {}

    /**
     * List the authenticated user's conversations (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = DB::table('agent_conversations')
            ->where('user_id', $user->getAuthIdentifier())
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'conversations' => $conversations->map(fn ($c): array => [
                'id' => $c->id,
                'title' => $c->title,
                'updated_at' => $c->updated_at,
            ]),
        ]);
    }

    /**
     * Get messages for a conversation (for switching/loading a chat).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $exists = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();

        if (! $exists) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        $messages = $this->conversationStore->getLatestConversationMessages($id, 100);

        return response()->json([
            'conversation_id' => $id,
            'messages' => $messages->map(fn ($m): array => [
                'role' => $m->role->value,
                'content' => $m->content ?? '',
            ])->all(),
        ]);
    }

    /**
     * Send a message and get the assistant reply.
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
            'current_page' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $message = mb_trim((string) $validated['message']);
        $conversationId = $validated['conversation_id'] ?? null;
        $currentPage = $validated['current_page'] ?? null;

        $defaultProvider = config('ai.default');
        $apiKey = config("ai.providers.{$defaultProvider}.key");
        if (empty($apiKey)) {
            $keyName = $defaultProvider === 'openrouter' ? 'OPENROUTER_API_KEY' : 'OPENAI_API_KEY';

            return response()->json([
                'error' => "AI chat is not configured. Add {$keyName} to your .env file (see .env.example).",
            ], 503);
        }

        try {
            $context = $this->contextBuilder->build($user, $currentPage);
            $agent = new ChatbotAgent;

            if ($conversationId) {
                $agent->continue($conversationId, $user);
            } else {
                $agent->forUser($user);
            }

            $promptWithContext = $context !== ''
                ? $context."\n\nUser message: ".$message
                : $message;

            $response = $agent->prompt($promptWithContext);

            return response()->json([
                'conversation_id' => $response->conversationId,
                'message' => [
                    'role' => 'assistant',
                    'content' => $response->text,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('Chat message failed', ['error' => $e->getMessage()]);

            $message = $e->getMessage();
            $lower = mb_strtolower($message);
            $isConfigError = $message === ''
                || str_contains($lower, 'api key')
                || str_contains($lower, 'api_key')
                || str_contains($lower, 'authentication')
                || str_contains($lower, 'invalid_api_key')
                || str_contains($lower, 'no api key');
            $isQuotaExceeded = str_contains($lower, 'quota')
                || str_contains($lower, 'resource_exhausted')
                || str_contains($lower, 'insufficient_quota')
                || str_contains($lower, 'billing')
                || str_contains($lower, 'usage limit')
                || str_contains($lower, 'usage_limit')
                || (str_contains($lower, 'rate limit') && (str_contains($lower, 'daily') || str_contains($lower, 'monthly') || str_contains($lower, 'exceeded')));
            $isRateLimited = str_contains($lower, 'rate limit') && ! $isQuotaExceeded;

            $defaultProvider = config('ai.default');
            $keyName = $defaultProvider === 'openrouter' ? 'OPENROUTER_API_KEY' : 'OPENAI_API_KEY';
            if ($isConfigError || empty(config("ai.providers.{$defaultProvider}.key"))) {
                $userMessage = "AI chat is not configured. Add {$keyName} to your .env file (see .env.example).";
            } elseif ($isQuotaExceeded) {
                $userMessage = 'The AI provider quota or usage limit has been exceeded. Check your '
                    .($defaultProvider === 'openrouter' ? 'OpenRouter' : 'OpenAI')
                    .' account balance and usage limits, then try again.';
            } elseif ($isRateLimited) {
                $userMessage = 'The AI provider is temporarily rate limiting requests. Please wait a minute and try again.';
            } else {
                $userMessage = 'Sorry, I could not process that. Please try again.';
            }

            $payload = ['error' => $userMessage];
            if (config('app.debug')) {
                $payload['debug_error'] = $message;
            }

            return response()->json($payload, 502);
        }
    }
}
