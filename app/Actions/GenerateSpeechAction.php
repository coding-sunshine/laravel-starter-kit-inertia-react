<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Integrations\Resemble\Requests\GenerateSpeechRequest;
use App\Http\Integrations\Resemble\ResembleConnector;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generate a speech clip using a Resemble.ai cloned voice.
 *
 * Deferred: If RESEMBLE_API_KEY is not set, this action logs the deferral
 * and returns null without throwing an exception.
 *
 * Falls back to the Vapi default TTS voice when no cloned voice is available.
 */
final readonly class GenerateSpeechAction
{
    public function __construct(private ResembleConnector $connector)
    {
        //
    }

    /**
     * @return array<string, mixed>|null
     */
    public function handle(
        User $user,
        string $text,
        ?string $voiceUuid = null,
        string $callbackUrl = '',
    ): ?array {
        if (! ResembleConnector::isConfigured()) {
            Log::info('GenerateSpeech: Resemble.ai not configured — voice generation deferred', [
                'user_id' => $user->id,
            ]);

            return null;
        }

        $projectUuid = (string) config('services.resemble.project_uuid');

        if (empty($projectUuid)) {
            Log::warning('GenerateSpeech: RESEMBLE_PROJECT_UUID not set — voice generation deferred');

            return null;
        }

        $resolvedVoiceUuid = $voiceUuid
            ?? ($user->extra_attributes['resemble_voice_uuid'] ?? null);

        if ($resolvedVoiceUuid === null) {
            Log::info('GenerateSpeech: No voice UUID available — falling back to default TTS', ['user_id' => $user->id]);

            return ['status' => 'deferred', 'reason' => 'no_voice_uuid', 'fallback' => 'vapi_default_tts'];
        }

        try {
            $request = new GenerateSpeechRequest($projectUuid, $resolvedVoiceUuid, $text, $callbackUrl);
            $response = $this->connector->send($request);

            $data = $response->json();

            Log::info('GenerateSpeech: Speech clip generated', [
                'user_id' => $user->id,
                'clip_uuid' => $data['item']['uuid'] ?? null,
            ]);

            return $data;
        } catch (Throwable $e) {
            Log::error('GenerateSpeech: Failed to generate speech', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
