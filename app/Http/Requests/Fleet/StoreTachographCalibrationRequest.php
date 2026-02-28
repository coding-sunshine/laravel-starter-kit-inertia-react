<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\TachographCalibrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTachographCalibrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\TachographCalibration::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'telematics_device_id' => ['nullable', 'integer', 'exists:telematics_devices,id'],
            'calibration_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'certificate_reference' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(array_column(TachographCalibrationStatus::cases(), 'value'))],
        ];
    }
}
