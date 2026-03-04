<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGeofenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Geofence::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'geofence_type' => ['required', 'string', 'in:circle,polygon,administrative_boundary'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'center_lat' => ['nullable', 'numeric'],
            'center_lng' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
