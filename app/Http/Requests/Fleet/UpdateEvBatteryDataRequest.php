<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\EvBatteryChargingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEvBatteryDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ev_battery_data')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'recorded_at' => ['required', 'date'],
            'soc_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'soh_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'voltage' => ['nullable', 'numeric', 'min:0'],
            'current_amps' => ['nullable', 'numeric'],
            'temperature_celsius' => ['nullable', 'integer'],
            'range_remaining_km' => ['nullable', 'integer', 'min:0'],
            'energy_consumed_kwh' => ['nullable', 'numeric', 'min:0'],
            'regenerative_energy_kwh' => ['nullable', 'numeric', 'min:0'],
            'charging_status' => ['required', 'string', Rule::in(array_column(EvBatteryChargingStatus::cases(), 'value'))],
            'battery_warnings' => ['nullable', 'array'],
            'battery_warnings.*' => ['string'],
        ];
    }
}
