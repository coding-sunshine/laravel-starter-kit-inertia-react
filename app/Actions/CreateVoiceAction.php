<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Integrations\Resemble\Requests\CreateVoiceRequest;
use App\Http\Integrations\Resemble\ResembleConnector;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Create a Resemble.ai voice clone for a user.
 *
 * Deferred: If RESEMBLE_API_KEY is not set, this action logs the deferral
 * and returns null without throwing an exception.
 *
 * When configured, creates a voice clone via the Resemble.ai API and stores
 * the voice UUID in the user's extra_attributes (or org settings).
 */
final readonly class CreateVoiceAction
{
    public function __construct(private ResembleConnector $connector)
    {
        //
    }

    public function handle(User $user, string $voiceName, string $callbackUrl = ''): ?string
    {
        if (! ResembleConnector::isConfigured()) {
            Log::info('CreateVoice: Resemble.ai not configured — voice cloning deferred', [
                'user_id' => $user->id,
                'voice_name' => $voiceName,
            ]);

            return null;
        }

        $projectUuid = (string) config('services.resemble.project_uuid');

        if (empty($projectUuid)) {
            Log::warning('CreateVoice: RESEMBLE_PROJECT_UUID not set — voice cloning deferred');

            return null;
        }

        try {
            $request = new CreateVoiceRequest($projectUuid, $voiceName, $callbackUrl);
            $response = $this->connector->send($request);

            $data = $response->json();
            $voiceUuid = $data['item']['uuid'] ?? null;

            if ($voiceUuid !== null) {
                $this->storeVoiceUuid($user, (string) $voiceUuid);
                Log::info('CreateVoice: Voice clone created', ['user_id' => $user->id, 'voice_uuid' => $voiceUuid]);
            }

            return $voiceUuid !== null ? (string) $voiceUuid : null;
        } catch (Throwable $e) {
            Log::error('CreateVoice: Failed to create voice clone', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function storeVoiceUuid(User $user, string $voiceUuid): void
    {
        $extraAttributes = $user->extra_attributes ?? [];
        $extraAttributes['resemble_voice_uuid'] = $voiceUuid;
        $user->update(['extra_attributes' => $extraAttributes]);
    }
}
