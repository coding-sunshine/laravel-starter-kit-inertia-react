<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\ConciergeAgent;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Property concierge — matches buyers to suitable properties via AI.
 */
final class ConciergeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('concierge/index');
    }

    public function match(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'message' => ['nullable', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401);

        $contact = isset($data['contact_id']) ? Contact::query()->find($data['contact_id']) : null;
        $message = $data['message'] ?? 'Find me suitable properties';

        if ($contact) {
            $name = mb_trim($contact->first_name.' '.($contact->last_name ?? ''));
            $message = "Match properties for buyer: {$name}. Budget/preferences: {$message}";
        }

        $conversationId = $data['conversation_id'] ?? null;

        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'user_id' => $user->id,
                'title' => 'Concierge: '.mb_substr($message, 0, 60),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $agent = ConciergeAgent::make(['user_id' => $user->id])
            ->continue($conversationId, $user);

        try {
            $response = $agent->run($message);

            return response()->json([
                'success' => true,
                'reply' => $response->text ?? '',
                'conversation_id' => $conversationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'reply' => 'Unable to match properties at this time. Please try again.',
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
