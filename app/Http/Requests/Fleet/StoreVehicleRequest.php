<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Vehicle::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registration' => ['required', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'size:17'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'vehicle_type' => ['required', 'string', 'in:car,van,truck,bus,motorcycle'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'current_driver_id' => ['nullable', 'exists:drivers,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ];
    }
}

