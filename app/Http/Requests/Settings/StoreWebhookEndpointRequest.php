<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = TenantContext::get();

        return $organization !== null
            && $this->user()?->canInOrganization('org.webhooks.manage', $organization);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
