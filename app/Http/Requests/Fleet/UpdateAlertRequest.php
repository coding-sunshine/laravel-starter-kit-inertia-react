<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\AlertSeverity;
use App\Enums\Fleet\AlertStatus;
use App\Enums\Fleet\AlertType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('alert')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alert_type' => ['required', 'string', Rule::in(array_column(AlertType::cases(), 'value'))],
            'severity' => ['required', 'string', Rule::in(array_column(AlertSeverity::cases(), 'value'))],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'entity_type' => ['nullable', 'string', 'max:50'],
            'entity_id' => ['nullable', 'integer'],
            'triggered_at' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(array_column(AlertStatus::cases(), 'value'))],
            'notification_sent' => ['boolean'],
            'escalation_level' => ['integer', 'min:0'],
            'resolution_notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
