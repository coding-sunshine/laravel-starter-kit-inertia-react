<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\VehicleCheckStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVehicleCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vehicle_check')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'vehicle_check_template_id' => ['required', 'integer', 'exists:vehicle_check_templates,id'],
            'performed_by_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'performed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'defect_id' => ['nullable', 'integer', 'exists:defects,id'],
            'check_date' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_column(VehicleCheckStatus::cases(), 'value'))],
        ];
    }
}
