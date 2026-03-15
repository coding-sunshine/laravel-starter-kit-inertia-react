<?php

declare(strict_types=1);

namespace App\Http\Integrations\Resemble\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Create a new voice clone in Resemble.ai.
 */
final class CreateVoiceRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $projectUuid,
        private readonly string $name,
        private readonly string $callbackUrl = '',
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/projects/{$this->projectUuid}/voices";
    }

    protected function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'callback_uri' => $this->callbackUrl,
        ];
    }
}
