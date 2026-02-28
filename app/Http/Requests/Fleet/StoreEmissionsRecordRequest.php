<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\EmissionsScope;
use App\Enums\Fleet\EmissionsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEmissionsRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\EmissionsRecord::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
            'scope' => ['required', 'string', Rule::in(array_column(EmissionsScope::cases(), 'value'))],
            'emissions_type' => ['required', 'string', Rule::in(array_column(EmissionsType::cases(), 'value'))],
            'record_date' => ['required', 'date'],
            'co2_kg' => ['required', 'numeric', 'min:0'],
            'fuel_consumed_litres' => ['nullable', 'numeric', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
