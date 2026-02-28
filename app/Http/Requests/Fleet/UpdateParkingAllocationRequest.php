<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateParkingAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('parking_allocation')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'allocated_from' => ['required', 'date'],
            'allocated_to' => ['nullable', 'date', 'after_or_equal:allocated_from'],
            'spot_identifier' => ['nullable', 'string', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
