<?php

declare(strict_types=1);

namespace App\Http\Integrations\Resemble\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Retrieve a voice clone from Resemble.ai.
 */
final class GetVoiceRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $projectUuid,
        private readonly string $voiceUuid,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/projects/{$this->projectUuid}/voices/{$this->voiceUuid}";
    }
}
