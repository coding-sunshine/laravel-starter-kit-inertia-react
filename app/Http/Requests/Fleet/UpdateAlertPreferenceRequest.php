<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAlertPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('alert_preference')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alert_type' => ['required', 'string', 'max:100'],
            'email_enabled' => ['boolean'],
            'sms_enabled' => ['boolean'],
            'push_enabled' => ['boolean'],
            'in_app_enabled' => ['boolean'],
            'escalation_minutes' => ['integer', 'min:0'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'weekend_enabled' => ['boolean'],
        ];
    }
}
