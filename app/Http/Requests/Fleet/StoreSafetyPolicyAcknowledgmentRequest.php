<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSafetyPolicyAcknowledgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\SafetyPolicyAcknowledgment::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'policy_type' => ['required', 'string', 'max:100'],
            'policy_reference' => ['nullable', 'string', 'max:100'],
            'policy_version' => ['nullable', 'string', 'max:50'],
            'acknowledged_at' => ['required', 'date'],
            'ip_address' => ['nullable', 'string', 'max:45'],
        ];
    }
}
