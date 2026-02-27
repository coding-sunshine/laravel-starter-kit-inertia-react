<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\VehicleComplianceStatus;
use App\Enums\Fleet\VehicleFuelType;
use App\Enums\Fleet\VehicleStatus;
use App\Enums\Fleet\VehicleType;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
        $orgId = TenantContext::id();

        return [
            'registration' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'registration')->where('organization_id', $orgId),
            ],
            'vin' => ['nullable', 'string', 'max:17'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['required', 'string', new Enum(VehicleFuelType::class)],
            'vehicle_type' => ['required', 'string', new Enum(VehicleType::class)],
            'home_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'current_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'status' => ['required', 'string', new Enum(VehicleStatus::class)],
            'compliance_status' => ['nullable', 'string', new Enum(VehicleComplianceStatus::class)],
        ];
    }

}

