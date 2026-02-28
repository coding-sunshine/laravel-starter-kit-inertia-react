<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ServiceScheduleIntervalType;
use App\Enums\Fleet\ServiceScheduleIntervalUnit;
use App\Enums\Fleet\ServiceScheduleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateServiceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('service_schedule')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'service_type' => ['required', 'string', Rule::in(array_column(ServiceScheduleType::cases(), 'value'))],
            'interval_type' => ['required', 'string', Rule::in(array_column(ServiceScheduleIntervalType::cases(), 'value'))],
            'interval_value' => ['required', 'integer', 'min:1'],
            'interval_unit' => ['required', 'string', Rule::in(array_column(ServiceScheduleIntervalUnit::cases(), 'value'))],
            'last_service_date' => ['nullable', 'date'],
            'last_service_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_due_date' => ['nullable', 'date'],
            'next_service_due_mileage' => ['nullable', 'integer', 'min:0'],
            'alert_days_before' => ['nullable', 'integer', 'min:0'],
            'alert_km_before' => ['nullable', 'integer', 'min:0'],
            'preferred_garage_id' => ['nullable', 'integer', 'exists:garages,id'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
