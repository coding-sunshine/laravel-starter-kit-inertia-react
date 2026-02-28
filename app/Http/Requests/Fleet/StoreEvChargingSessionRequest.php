<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\EvChargingSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEvChargingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\EvChargingSession::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'charging_station_id' => ['required', 'integer', 'exists:ev_charging_stations,id'],
            'connector_id' => ['nullable', 'string', 'max:50'],
            'session_id' => ['required', 'string', 'max:100', 'unique:ev_charging_sessions,session_id'],
            'start_timestamp' => ['required', 'date'],
            'end_timestamp' => ['nullable', 'date', 'after_or_equal:start_timestamp'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'energy_delivered_kwh' => ['nullable', 'numeric', 'min:0'],
            'charging_rate_kw' => ['nullable', 'numeric', 'min:0'],
            'initial_soc_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'final_soc_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'cost_per_kwh' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'session_type' => ['required', 'string', Rule::in(array_column(EvChargingSessionType::cases(), 'value'))],
            'interrupted' => ['boolean'],
            'interruption_reason' => ['nullable', 'string', 'max:200'],
        ];
    }
}
