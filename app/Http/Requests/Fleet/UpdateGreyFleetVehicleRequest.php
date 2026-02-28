<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateGreyFleetVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('grey_fleet_vehicle')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'registration' => ['nullable', 'string', 'max:20'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'colour' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:20'],
            'engine_cc' => ['nullable', 'integer', 'min:0'],
            'is_approved' => ['boolean'],
            'approval_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
