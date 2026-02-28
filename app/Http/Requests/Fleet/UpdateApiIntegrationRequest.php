<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ApiIntegrationSyncStatus;
use App\Enums\Fleet\ApiIntegrationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateApiIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('api_integration')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'integration_name' => ['required', 'string', 'max:200'],
            'integration_type' => ['required', 'string', Rule::in(array_column(ApiIntegrationType::cases(), 'value'))],
            'provider_name' => ['required', 'string', 'max:200'],
            'api_endpoint' => ['nullable', 'string', 'max:500'],
            'authentication_type' => ['required', 'string', 'max:50'],
            'authentication_config' => ['nullable', 'array'],
            'data_sync_frequency' => ['nullable', 'string', 'max:20'],
            'sync_status' => ['nullable', 'string', Rule::in(array_column(ApiIntegrationSyncStatus::cases(), 'value'))],
            'rate_limit_per_hour' => ['nullable', 'integer', 'min:0'],
            'monthly_limit' => ['nullable', 'integer', 'min:0'],
            'webhook_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
