<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\BotV2Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Bot In A Box v2 — unified CRM chat interface.
 */
final class BotV2Controller extends Controller
{
    public function index(): Response
    {
        return Inertia::render('bot/index');
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401);

        $conversationId = $data['conversation_id'] ?? null;

        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'user_id' => $user->id,
                'title' => mb_substr($data['message'], 0, 80),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $agent = BotV2Agent::make(['user_id' => $user->id])
            ->continue($conversationId, $user);

        try {
            $response = $agent->run($data['message']);

            return response()->json([
                'success' => true,
                'reply' => $response->text ?? '',
                'conversation_id' => $conversationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'reply' => 'Sorry, I encountered an error. Please try again.',
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
