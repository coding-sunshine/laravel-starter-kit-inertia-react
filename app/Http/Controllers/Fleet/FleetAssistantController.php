<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Ai\Agents\FleetAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\ReasoningStart;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Exceptions\AiException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class FleetAssistantController extends Controller
{
    private const CONVERSATION_TITLE = 'Fleet Assistant';

    private const AGENT_FLEET = 'fleet_assistant';

    /** @param \Illuminate\Database\Query\Builder $query */
    private function scopeFleetConversations($query): void
    {
        $query->where(function ($q): void {
            $q->where('agent', self::AGENT_FLEET)
                ->orWhere(fn ($q2): mixed => $q2->whereNull('agent')->where('title', self::CONVERSATION_TITLE));
        });
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $conversations = [];
        $initialMessages = [];
        $conversationId = null;

        if ($user !== null) {
            $conversations = DB::table('agent_conversations')
                ->where('user_id', $user->id)
                ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(['id', 'title', 'updated_at'])
                ->map(fn ($row): array => [
                    'id' => $row->id,
                    'title' => $row->title,
                    'updated_at' => $row->updated_at,
                ])
                ->all();

            $requestedId = $request->query('conversation_id');
            if (is_string($requestedId) && $requestedId !== '') {
                $conv = DB::table('agent_conversations')
                    ->where('id', $requestedId)
                    ->where('user_id', $user->id)
                    ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
                    ->first();
                if ($conv !== null) {
                    $conversationId = $conv->id;
                    $initialMessages = DB::table('agent_conversation_messages')
                        ->where('conversation_id', $conversationId)
                        ->orderBy('created_at')
                        ->get(['id', 'role', 'content'])
                        ->map(fn ($m): array => [
                            'id' => $m->id,
                            'role' => $m->role,
                            'content' => (string) ($m->content ?? ''),
                        ])
                        ->values()
                        ->all();
                }
            }
        }

        return Inertia::render('Fleet/Assistant/Index', [
            'conversations' => $conversations,
            'initial_messages' => $initialMessages,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Send a message to the Fleet Assistant and return the reply.
     */
    public function prompt(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:16000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ]);

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organizationId = \App\Services\TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization selected. Switch to an organization first.'], 403);
        }

        $conversationId = $request->input('conversation_id');
        $newConversationId = null;

        if ($conversationId === null || $conversationId === '') {
            $newConversationId = (string) Str::uuid();
            $title = Str::limit($request->input('message'), 60) ?: self::CONVERSATION_TITLE;
            DB::table('agent_conversations')->insert([
                'id' => $newConversationId,
                'user_id' => $user->id,
                'agent' => self::AGENT_FLEET,
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $conversationId = $newConversationId;
        } else {
            $exists = DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
                ->exists();
            if (! $exists) {
                return response()->json(['message' => 'Invalid conversation.'], 422);
            }
        }

        $agent = FleetAssistant::make($organizationId, $user->id)->continue($conversationId, $user);

        try {
            $response = $agent->prompt($request->input('message'));
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'AI request failed.',
                'error' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'reply' => $response->text,
            'conversation_id' => $conversationId,
            'new_conversation_id' => $newConversationId,
        ]);
    }

    /**
     * Stream a reply from the Fleet Assistant (NDJSON, same protocol as /api/chat).
     */
    public function stream(Request $request): JsonResponse|StreamedResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:16000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ]);

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organizationId = \App\Services\TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization selected. Switch to an organization first.'], 403);
        }

        $conversationId = $request->input('conversation_id');
        $newConversationId = null;

        if ($conversationId === null || $conversationId === '') {
            $newConversationId = (string) Str::uuid();
            $title = Str::limit($request->input('message'), 60) ?: self::CONVERSATION_TITLE;
            DB::table('agent_conversations')->insert([
                'id' => $newConversationId,
                'user_id' => $user->id,
                'agent' => self::AGENT_FLEET,
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $conversationId = $newConversationId;
        } else {
            $exists = DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
                ->exists();
            if (! $exists) {
                return response()->json(['message' => 'Invalid conversation.'], 422);
            }
        }

        $agent = FleetAssistant::make($organizationId, $user->id)->continue($conversationId, $user);

        try {
            $stream = $agent->stream($request->input('message'));
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'AI request failed.',
                'error' => $e->getMessage(),
            ], 502);
        }

        $runId = null;
        $messageId = null;
        $contentAccumulator = '';

        return response()->stream(
            function () use ($stream, $newConversationId, &$runId, &$messageId, &$contentAccumulator): void {
                if (ob_get_level() !== 0) {
                    ob_end_clean();
                }
                try {
                    foreach ($stream as $event) {
                        if (connection_aborted() !== 0) {
                            return;
                        }

                        $ts = (int) (microtime(true) * 1000);

                        if ($event instanceof StreamStart) {
                            $runId = $event->id;
                            echo json_encode([
                                'type' => 'RUN_STARTED',
                                'timestamp' => $ts,
                                'runId' => $runId,
                            ])."\n";
                            if ($newConversationId !== null) {
                                echo json_encode([
                                    'type' => 'CONVERSATION_CREATED',
                                    'timestamp' => $ts,
                                    'conversationId' => $newConversationId,
                                ])."\n";
                            }
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            continue;
                        }

                        if ($event instanceof TextStart) {
                            if ($messageId === null) {
                                $messageId = $runId ?? $event->messageId;
                                echo json_encode([
                                    'type' => 'TEXT_MESSAGE_START',
                                    'timestamp' => $ts,
                                    'messageId' => $messageId,
                                    'role' => 'assistant',
                                ])."\n";
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                            continue;
                        }

                        if ($event instanceof ReasoningStart) {
                            if ($messageId === null) {
                                $messageId = $runId ?? $event->reasoningId;
                                echo json_encode([
                                    'type' => 'TEXT_MESSAGE_START',
                                    'timestamp' => $ts,
                                    'messageId' => $messageId,
                                    'role' => 'assistant',
                                ])."\n";
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                            continue;
                        }

                        if ($event instanceof ReasoningDelta) {
                            if ($messageId === null) {
                                $messageId = $runId ?? $event->reasoningId;
                                echo json_encode([
                                    'type' => 'TEXT_MESSAGE_START',
                                    'timestamp' => $ts,
                                    'messageId' => $messageId,
                                    'role' => 'assistant',
                                ])."\n";
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                            $contentAccumulator .= $event->delta;
                            echo json_encode([
                                'type' => 'TEXT_MESSAGE_CONTENT',
                                'timestamp' => $ts,
                                'messageId' => $messageId,
                                'delta' => $event->delta,
                                'content' => $contentAccumulator,
                            ])."\n";
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            continue;
                        }

                        if ($event instanceof TextDelta) {
                            if ($messageId === null) {
                                $messageId = $runId ?? $event->messageId;
                                echo json_encode([
                                    'type' => 'TEXT_MESSAGE_START',
                                    'timestamp' => $ts,
                                    'messageId' => $messageId,
                                    'role' => 'assistant',
                                ])."\n";
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                            $contentAccumulator .= $event->delta;
                            echo json_encode([
                                'type' => 'TEXT_MESSAGE_CONTENT',
                                'timestamp' => $ts,
                                'messageId' => $messageId,
                                'delta' => $event->delta,
                                'content' => $contentAccumulator,
                            ])."\n";
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            continue;
                        }

                        if ($event instanceof TextEnd) {
                            echo json_encode([
                                'type' => 'TEXT_MESSAGE_END',
                                'timestamp' => $ts,
                                'messageId' => $messageId ?? $event->messageId,
                            ])."\n";
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            continue;
                        }

                        if ($event instanceof StreamEnd) {
                            $finishReason = match ($event->reason ?? '') {
                                'length' => 'length',
                                'content_filter' => 'content_filter',
                                default => 'stop',
                            };
                            $usage = $event->usage;
                            echo json_encode([
                                'type' => 'TEXT_MESSAGE_END',
                                'timestamp' => $ts,
                                'messageId' => $messageId ?? '',
                            ])."\n";
                            echo json_encode([
                                'type' => 'RUN_FINISHED',
                                'timestamp' => $ts,
                                'runId' => $runId ?? '',
                                'finishReason' => $finishReason,
                                'usage' => [
                                    'promptTokens' => $usage->promptTokens ?? 0,
                                    'completionTokens' => $usage->completionTokens ?? 0,
                                    'totalTokens' => ($usage->promptTokens ?? 0) + ($usage->completionTokens ?? 0),
                                ],
                            ])."\n";
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                        }
                    }
                } catch (Throwable $e) {
                    $message = $e->getMessage();
                    if ($e instanceof AiException && str_contains($message, '401')) {
                        $message = 'OpenRouter API key invalid or missing. Set OPENROUTER_API_KEY in .env and run php artisan config:clear.';
                    }
                    echo json_encode([
                        'type' => 'ERROR',
                        'message' => $message,
                    ])."\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                } finally {
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                }
            },
            200,
            [
                'Content-Type' => 'application/x-ndjson',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }

    /**
     * Rename a Fleet Assistant conversation (web, session auth).
     */
    public function updateConversation(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:255']]);
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $updated = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
            ->update(['title' => $request->input('title'), 'updated_at' => now()]);

        if (! $updated) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => ['id' => $id, 'title' => $request->input('title')]]);
        }

        return redirect()->back();
    }

    /**
     * Delete a Fleet Assistant conversation and its messages (web, session auth).
     */
    public function destroyConversation(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $deleted = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where(function ($q): void {
                    $this->scopeFleetConversations($q);
                })
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        DB::table('agent_conversation_messages')->where('conversation_id', $id)->delete();

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->to($id === $request->query('conversation_id') ? '/fleet/assistant' : $request->header('Referer', '/fleet/assistant'));
    }
}
