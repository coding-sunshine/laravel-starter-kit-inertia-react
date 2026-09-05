<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\VehicleDispatch;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVehicleDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispatch = $this->route('vehicle_dispatch');

        if (! $dispatch instanceof VehicleDispatch) {
            return false;
        }

        $user = $this->user();

        return $user?->canAccessSiding($dispatch->siding_id) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'serial_no' => ['nullable', 'integer', 'min:0'],
            'ref_no' => ['nullable', 'integer', 'min:0'],
            'permit_no' => ['required', 'string', 'max:50'],
            'pass_no' => ['required', 'string', 'max:100'],
            'stack_do_no' => ['nullable', 'string', 'max:100'],
            'issued_on' => ['nullable', 'date'],
            'truck_regd_no' => ['required', 'string', 'max:20'],
            'mineral' => ['required', 'string', 'max:50'],
            'mineral_type' => ['nullable', 'string', 'max:50'],
            'mineral_weight' => ['required', 'numeric', 'min:0'],
            'source' => ['nullable', 'string'],
            'destination' => ['nullable', 'string'],
            'consignee' => ['nullable', 'string'],
            'check_gate' => ['nullable', 'string', 'max:100'],
            'distance_km' => ['nullable', 'integer', 'min:0'],
            'shift' => ['nullable', 'string', 'max:20'],
        ];
    }
}
