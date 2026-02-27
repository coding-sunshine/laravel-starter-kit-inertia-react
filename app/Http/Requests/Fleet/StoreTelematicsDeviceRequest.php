<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTelematicsDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\TelematicsDevice::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'device_id' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,inactive,suspended,decommissioned'],
            'installed_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
