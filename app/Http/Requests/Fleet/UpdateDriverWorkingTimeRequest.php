<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDriverWorkingTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('driver_working_time')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'date' => ['required', 'date'],
            'shift_start_time' => ['nullable', 'date'],
            'shift_end_time' => ['nullable', 'date'],
            'break_time_minutes' => ['nullable', 'integer', 'min:0'],
            'driving_time_minutes' => ['nullable', 'integer', 'min:0'],
            'other_work_time_minutes' => ['nullable', 'integer', 'min:0'],
            'rest_time_minutes' => ['nullable', 'integer', 'min:0'],
            'total_duty_time_minutes' => ['nullable', 'integer', 'min:0'],
            'wtd_compliant' => ['nullable', 'boolean'],
            'rtd_compliant' => ['nullable', 'boolean'],
            'manual_entry' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
