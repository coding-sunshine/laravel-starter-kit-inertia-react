<?php

declare(strict_types=1);

namespace App\Http\Integrations\Resemble\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Generate a speech clip using a cloned voice in Resemble.ai.
 */
final class GenerateSpeechRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $projectUuid,
        private readonly string $voiceUuid,
        private readonly string $text,
        private readonly string $callbackUrl = '',
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/projects/{$this->projectUuid}/clips";
    }

    protected function defaultBody(): array
    {
        return [
            'voice_uuid' => $this->voiceUuid,
            'body' => $this->text,
            'callback_uri' => $this->callbackUrl,
            'is_public' => false,
            'is_archived' => false,
        ];
    }
}
