<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('route')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'route_type' => ['required', 'string', 'in:planned,ad_hoc'],
            'description' => ['nullable', 'string'],
            'start_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'end_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'estimated_distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
