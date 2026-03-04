<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\Agents\AssistantAgent;
use App\Ai\Agents\ContactAssistantAgent;
use App\Ai\Agents\PropertySalesAgent;
use App\Services\PrismService;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

use function array_reverse;
use function is_array;

final class ChatController
{
    /**
     * Chat completion as NDJSON (TanStack AG-UI protocol) for use with fetchHttpStream.
     * Accepts optional conversation_id to continue a conversation; creates one when absent.
     * Messages may have either top-level "content" (string) or "parts" (TanStack UIMessage format).
     *
     * Uses non-streaming prompt() internally because Prism's SSE stream reader
     * does not work reliably under PHP-FPM (Herd). The NDJSON events are emitted
     * from the completed response so the frontend AG-UI client still works correctly.
     */
    public function __invoke(Request $request): Response|StreamedResponse
    {
        $request->validate([
            'messages' => ['required', 'array'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'agent' => ['nullable', 'string', 'in:general,contact,property'],
            'conversation_id' => ['nullable', 'string', 'uuid', function (string $attr, string $value, Closure $fail) use ($request): void {
                $user = $request->user();
                if ($user === null) {
                    $fail('Unauthenticated.');

                    return;
                }
                $exists = DB::table('agent_conversations')
                    ->where('id', $value)
                    ->where('user_id', $user->id)
                    ->exists();
                if (! $exists) {
                    $fail('The selected conversation is invalid.');
                }
            }],
        ]);

        $user = $request->user();
        abort_if($user === null, 401);

        /** @var array<int, array{role?: string, content?: mixed, parts?: array<int, array{type?: string, content?: string}>}> $messages */
        $messages = $request->input('messages', []);
        $lastUser = $this->getMessageContent(array_reverse($messages), 'user');
        $prompt = $lastUser ?? '';

        if ($prompt === '') {
            return response()->json([
                'message' => 'The messages.0.content field is required.',
                'errors' => ['messages' => ['The last user message must have content or text parts.']],
            ], 422);
        }

        $defaultProvider = config('ai.default', 'openrouter');
        $providerKey = config("ai.providers.{$defaultProvider}.key");

        if (empty($providerKey)) {
            $envKey = mb_strtoupper(str_replace('-', '_', (string) $defaultProvider)).'_API_KEY';

            return response()->json([
                'message' => 'AI provider is not configured. Set '.$envKey.' in your .env or in Settings > AI / Prism.',
            ], 503);
        }

        $conversationIdInput = $request->input('conversation_id');
        $newConversationId = null;
        $conversationId = is_string($conversationIdInput) && $conversationIdInput !== '' ? $conversationIdInput : null;

        if ($conversationId === null) {
            $newConversationId = (string) Str::uuid();
            DB::table('agent_conversations')->insert([
                'id' => $newConversationId,
                'user_id' => $user->id,
                'organization_id' => TenantContext::id(),
                'title' => 'New chat',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $conversationId = $newConversationId;
        }

        $agentType = $request->input('agent', 'general');
        $context = ['user_id' => $user->id];

        $agent = match ($agentType) {
            'contact' => ContactAssistantAgent::make($context)->continue($conversationId, $user),
            'property' => PropertySalesAgent::make()->continue($conversationId, $user),
            default => AssistantAgent::make($context)->continue($conversationId, $user),
        };

        // Check if this is a confirmation response for creating from brochure processing
        $lowerPrompt = strtolower(trim($prompt));
        if (in_array($lowerPrompt, ['yes', 'y', 'create', 'confirm']) && $agentType === 'property') {
            // Look for the most recent processing ID in the conversation
            $messages = $request->input('messages', []);
            $processingId = null;

            // Search for processing ID in recent messages
            for ($i = count($messages) - 1; $i >= 0; $i--) {
                $content = $messages[$i]['content'] ?? '';
                if (preg_match('/Processing ID:\s*(\d+)/', $content, $matches)) {
                    $processingId = (int) $matches[1];
                    break;
                }
            }

            if ($processingId) {
                try {
                    $createTool = new \App\Ai\Tools\CreateFromBrochureProcessing();
                    $toolRequest = new \Laravel\Ai\Tools\Request([
                        'processing_id' => $processingId,
                        'confirmation' => 'yes'
                    ]);
                    $text = $createTool->handle($toolRequest);
                } catch (Throwable $e) {
                    logger()->error('[Chat] Create from brochure processing failed', [
                        'processing_id' => $processingId,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                    $text = "❌ Error creating project/lot: {$e->getMessage()}";
                }
            } else {
                // Fallback to normal AI response if no processing ID found
                $response = $agent->prompt($prompt);
                $text = $response->text ?? '';
            }
        }
        // Check if this is a file upload request that needs document processing
        elseif (str_contains($prompt, 'file_path:') || str_contains($prompt, 'uploads/documents/')) {
            if (preg_match('/uploads\/documents\/[^\s]+/', $prompt, $matches)) {
                $filePath = $matches[0];
                try {
                    $documentProcessor = new \App\Ai\Tools\DocumentProcessor();
                    $toolRequest = new \Laravel\Ai\Tools\Request(['file_path' => $filePath, 'type' => 'auto']);
                    $processedFileText = (string) $documentProcessor->handle($toolRequest);
                    $text = $processedFileText;
                } catch (Throwable $e) {
                    logger()->error('[Chat] Document processing failed', [
                        'file_path' => $filePath,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                    $text = "❌ Error processing document: {$e->getMessage()}";
                }
            } else {
                $text = '❌ Could not find a file path in the message. Please try uploading the file again.';
            }
        } else {
            // Use non-streaming prompt() — Prism streaming breaks under PHP-FPM (Herd)
            // because its byte-by-byte SSE reader doesn't handle FastCGI buffering.
            try {
                $response = $agent->prompt($prompt);
            } catch (Throwable $e) {
                logger()->error('[Chat] Agent prompt failed', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'previous' => $e->getPrevious() ? get_class($e->getPrevious()).': '.$e->getPrevious()->getMessage() : null,
                ]);

                return response()->json([
                    'message' => 'AI request failed: '.$e->getMessage(),
                ], 502);
            }

            $text = $response->text ?? '';
        }
        $runId = (string) Str::ulid();
        $messageId = (string) Str::ulid();

        return response()->stream(
            function () use ($text, $runId, $messageId, $newConversationId, $prompt): void {
                if (ob_get_level() !== 0) {
                    ob_end_clean();
                }

                $ts = (int) (microtime(true) * 1000);

                // RUN_STARTED
                echo json_encode(['type' => 'RUN_STARTED', 'timestamp' => $ts, 'runId' => $runId])."\n";

                // CONVERSATION_CREATED (if new)
                if ($newConversationId !== null) {
                    echo json_encode([
                        'type' => 'CONVERSATION_CREATED',
                        'timestamp' => $ts,
                        'conversationId' => $newConversationId,
                    ])."\n";
                }

                // TEXT_MESSAGE_START
                echo json_encode([
                    'type' => 'TEXT_MESSAGE_START',
                    'timestamp' => $ts,
                    'messageId' => $messageId,
                    'role' => 'assistant',
                ])."\n";

                // TEXT_MESSAGE_CONTENT — emit the full text as a single delta
                if ($text !== '') {
                    echo json_encode([
                        'type' => 'TEXT_MESSAGE_CONTENT',
                        'timestamp' => $ts,
                        'messageId' => $messageId,
                        'delta' => $text,
                        'content' => $text,
                    ])."\n";
                }

                // TEXT_MESSAGE_END
                echo json_encode([
                    'type' => 'TEXT_MESSAGE_END',
                    'timestamp' => $ts,
                    'messageId' => $messageId,
                ])."\n";

                // RUN_FINISHED
                echo json_encode([
                    'type' => 'RUN_FINISHED',
                    'timestamp' => $ts,
                    'runId' => $runId,
                    'finishReason' => 'stop',
                    'usage' => [
                        'promptTokens' => 0,
                        'completionTokens' => 0,
                        'totalTokens' => 0,
                    ],
                ])."\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Generate AI title for new conversations
                if ($newConversationId !== null && $text !== '') {
                    try {
                        $titlePrompt = 'Generate a concise 3-5 word title for this conversation. '
                            ."Reply with ONLY the title, no quotes or punctuation at the end.\n\n"
                            ."User: {$prompt}\n"
                            .'Assistant: '.Str::limit($text, 500);

                        $generatedTitle = Str::limit(
                            mb_trim(resolve(PrismService::class)->generate($titlePrompt)->text),
                            100,
                        );

                        if ($generatedTitle !== '') {
                            DB::table('agent_conversations')
                                ->where('id', $newConversationId)
                                ->update(['title' => $generatedTitle, 'updated_at' => now()]);

                            $ts = (int) (microtime(true) * 1000);
                            echo json_encode([
                                'type' => 'CONVERSATION_TITLE_UPDATED',
                                'timestamp' => $ts,
                                'conversationId' => $newConversationId,
                                'title' => $generatedTitle,
                            ])."\n";
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                        }
                    } catch (Throwable) {
                        // Silent fail — "New chat" title remains
                    }
                }
            },
            200,
            [
                'Content-Type' => 'application/x-ndjson',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }

    /**
     * Extract text content from a message that may have "content" (string) or "parts" (TanStack UIMessage).
     *
     * @param  array<int, array{role?: string, content?: mixed, parts?: array<int, array{type?: string, content?: string}>}>  $messages
     */
    private function getMessageContent(array $messages, string $role): ?string
    {
        foreach ($messages as $m) {
            if (($m['role'] ?? '') !== $role) {
                continue;
            }
            if (isset($m['content']) && \is_string($m['content'])) {
                return $m['content'];
            }
            $parts = $m['parts'] ?? [];
            if (is_array($parts)) {
                $text = [];
                foreach ($parts as $part) {
                    if (($part['type'] ?? '') === 'text' && isset($part['content']) && \is_string($part['content'])) {
                        $text[] = $part['content'];
                    }
                }
                if ($text !== []) {
                    return implode('', $text);
                }
            }
        }

        return null;
    }
}
