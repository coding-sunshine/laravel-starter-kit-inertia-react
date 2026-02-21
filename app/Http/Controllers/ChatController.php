<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\ChatbotAgent;
use App\Ai\ChatContextBuilder;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\ConversationStore;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * Return loading rakes with ≤30 min free time remaining for proactive warnings.
     */
    public function demurrageWarnings(Request $request): JsonResponse
    {
        $user = $request->user();
        $sidingIds = $this->sidingIdsForUser($user);

        if ($sidingIds === []) {
            return response()->json(['warnings' => []]);
        }

        $rakes = Rake::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->whereNotNull('loading_start_time')
            ->whereNotNull('free_time_minutes')
            ->get();

        $warnings = [];
        foreach ($rakes as $rake) {
            $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
            $remaining = (int) now()->diffInMinutes($end, false);

            if ($remaining <= 30) {
                $warnings[] = [
                    'rake_number' => $rake->rake_number,
                    'siding_name' => $rake->siding?->name ?? 'Unknown',
                    'remaining_minutes' => max(0, $remaining),
                ];
            }
        }

        // Sort by most urgent first
        usort($warnings, fn ($a, $b) => $a['remaining_minutes'] <=> $b['remaining_minutes']);

        return response()->json(['warnings' => array_slice($warnings, 0, 5)]);
    }

    /**
     * Send a message and get the assistant reply (synchronous).
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

        if (! $this->isAiConfigured()) {
            return response()->json([
                'error' => $this->configErrorMessage(),
            ], 503);
        }

        try {
            $context = $this->contextBuilder->build($user, $currentPage);
            $sidingIds = $this->sidingIdsForUser($user);
            $memoryContext = ['user_id' => $user->getAuthIdentifier()];
            $agent = new ChatbotAgent($sidingIds, $memoryContext);

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
            return $this->handleChatError($e);
        }
    }

    /**
     * Stream a chat response via SSE for real-time token display.
     */
    public function stream(Request $request): StreamedResponse|JsonResponse
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

        if (! $this->isAiConfigured()) {
            return response()->json([
                'error' => $this->configErrorMessage(),
            ], 503);
        }

        try {
            $context = $this->contextBuilder->build($user, $currentPage);
            $sidingIds = $this->sidingIdsForUser($user);
            $memoryContext = ['user_id' => $user->getAuthIdentifier()];
            $agent = new ChatbotAgent($sidingIds, $memoryContext);

            if ($conversationId) {
                $agent->continue($conversationId, $user);
            } else {
                $agent->forUser($user);
            }

            $promptWithContext = $context !== ''
                ? $context."\n\nUser message: ".$message
                : $message;

            $streamResponse = $agent->stream($promptWithContext);

            return new StreamedResponse(function () use ($streamResponse): void {
                $conversationId = null;

                foreach ($streamResponse as $event) {
                    if (property_exists($event, 'conversationId') && $event->conversationId) {
                        $conversationId = $event->conversationId;
                    }

                    if (property_exists($event, 'text') && $event->text !== '') {
                        echo 'data: '.json_encode(['type' => 'text', 'content' => $event->text])."\n\n";
                        ob_flush();
                        flush();
                    }
                }

                // Send completion event with conversation ID
                $finalData = ['type' => 'done'];
                if ($conversationId) {
                    $finalData['conversation_id'] = $conversationId;
                }
                echo 'data: '.json_encode($finalData)."\n\n";
                ob_flush();
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (Throwable $e) {
            return $this->handleChatError($e);
        }
    }

    /**
     * @return array<int>
     */
    private function sidingIdsForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Siding::query()->pluck('id')->all();
        }

        return $user->accessibleSidings()->get()->pluck('id')->all();
    }

    private function isAiConfigured(): bool
    {
        $defaultProvider = config('ai.default');

        return ! empty(config("ai.providers.{$defaultProvider}.key"));
    }

    private function configErrorMessage(): string
    {
        $defaultProvider = config('ai.default');
        $keyName = $defaultProvider === 'openrouter' ? 'OPENROUTER_API_KEY' : 'OPENAI_API_KEY';

        return "AI chat is not configured. Add {$keyName} to your .env file (see .env.example).";
    }

    private function handleChatError(Throwable $e): JsonResponse
    {
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
        if ($isConfigError || ! $this->isAiConfigured()) {
            $userMessage = $this->configErrorMessage();
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
