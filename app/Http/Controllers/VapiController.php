<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Contact;
use App\Services\VapiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Handle Vapi voice AI webhook events and display call logs.
 */
final class VapiController extends Controller
{
    public function __construct(private VapiService $vapiService)
    {
        //
    }

    public function index(): InertiaResponse
    {
        $callLogs = CallLog::query()
            ->with('contact:id,first_name,last_name')
            ->latest('called_at')
            ->paginate(30);

        return Inertia::render('call-logs/index', [
            'call_logs' => $callLogs,
            'vapi_configured' => $this->vapiService->isConfigured(),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = $payload['message']['type'] ?? $payload['type'] ?? null;

        match ($type) {
            'call-started', 'call.created' => $this->handleCallStarted($payload),
            'call-ended', 'call.ended' => $this->handleCallEnded($payload),
            'transcript' => $this->handleTranscript($payload),
            default => null,
        };

        return response()->json(['received' => true], Response::HTTP_OK);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCallStarted(array $payload): void
    {
        $call = $payload['message']['call'] ?? $payload['call'] ?? $payload;
        $callSid = $call['id'] ?? null;

        if (! $callSid) {
            return;
        }

        $contactId = null;
        $metadata = $call['metadata'] ?? [];
        if (isset($metadata['contact_id'])) {
            $contactId = (int) $metadata['contact_id'];
            if (! Contact::query()->where('id', $contactId)->exists()) {
                $contactId = null;
            }
        }

        CallLog::query()->updateOrCreate(
            ['call_sid' => $callSid],
            [
                'contact_id' => $contactId,
                'direction' => $call['type'] ?? 'inbound',
                'vapi_metadata' => $call,
                'called_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCallEnded(array $payload): void
    {
        $call = $payload['message']['call'] ?? $payload['call'] ?? $payload;
        $callSid = $call['id'] ?? null;

        if (! $callSid) {
            return;
        }

        $endedReason = $call['endedReason'] ?? null;
        $outcome = match ($endedReason) {
            'customer-did-not-answer' => 'no-answer',
            'customer-hung-up' => 'completed',
            'voicemail' => 'voicemail',
            default => 'completed',
        };

        CallLog::query()
            ->where('call_sid', $callSid)
            ->update([
                'duration_seconds' => (int) ($call['duration'] ?? 0),
                'outcome' => $outcome,
                'vapi_metadata' => $call,
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleTranscript(array $payload): void
    {
        $call = $payload['message']['call'] ?? $payload['call'] ?? [];
        $callSid = $call['id'] ?? null;
        $transcript = $payload['message']['transcript'] ?? $payload['transcript'] ?? null;

        if (! $callSid || ! $transcript) {
            return;
        }

        $sentiment = $this->inferSentiment($transcript);

        CallLog::query()
            ->where('call_sid', $callSid)
            ->update([
                'transcript' => $transcript,
                'sentiment' => $sentiment,
            ]);
    }

    private function inferSentiment(string $transcript): string
    {
        $positiveWords = ['interested', 'great', 'love', 'perfect', 'yes', 'definitely', 'excited', 'happy'];
        $negativeWords = ['not interested', 'no thanks', 'remove', 'stop', 'angry', 'frustrated', 'never'];

        $lower = mb_strtolower($transcript);

        foreach ($negativeWords as $word) {
            if (str_contains($lower, $word)) {
                return 'negative';
            }
        }

        foreach ($positiveWords as $word) {
            if (str_contains($lower, $word)) {
                return 'positive';
            }
        }

        return 'neutral';
    }
}
