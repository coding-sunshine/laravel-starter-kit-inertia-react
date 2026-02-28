<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\VehicleDiscStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleDiscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\VehicleDisc::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'operator_licence_id' => ['required', 'integer', 'exists:operator_licences,id'],
            'disc_number' => ['required', 'string', 'max:50'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'status' => ['nullable', 'string', Rule::in(array_column(VehicleDiscStatus::cases(), 'value'))],
        ];
    }
}
