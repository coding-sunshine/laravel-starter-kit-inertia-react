<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\DriverWellnessSleepQuality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDriverWellnessRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('driver_wellness_record')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'record_date' => ['required', 'date'],
            'fatigue_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rest_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'sleep_quality' => ['nullable', 'string', Rule::in(array_column(DriverWellnessSleepQuality::cases(), 'value'))],
            'mood' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
