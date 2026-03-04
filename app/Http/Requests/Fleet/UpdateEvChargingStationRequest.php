<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateEvChargingStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ev_charging_station')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'operator' => ['nullable', 'string', 'max:100'],
            'network' => ['nullable', 'string', 'max:100'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'access_type' => ['required', 'string', 'in:public,private,restricted'],
            'total_connectors' => ['nullable', 'integer', 'min:1'],
            'available_connectors' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:operational,maintenance,out_of_service'],
        ];
    }
}
