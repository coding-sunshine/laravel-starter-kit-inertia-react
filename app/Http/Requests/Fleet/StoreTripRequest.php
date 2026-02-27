<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Trip::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'start_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'end_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'planned_start_time' => ['nullable', 'date'],
            'planned_end_time' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
