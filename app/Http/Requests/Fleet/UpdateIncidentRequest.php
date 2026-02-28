<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\FaultDetermination;
use App\Enums\Fleet\IncidentSeverity;
use App\Enums\Fleet\IncidentStatus;
use App\Enums\Fleet\IncidentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('incident')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'incident_number' => ['required', 'string', 'max:50'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['required', 'date_format:H:i'],
            'incident_type' => ['required', 'string', Rule::in(array_column(IncidentType::cases(), 'value'))],
            'severity' => ['required', 'string', Rule::in(array_column(IncidentSeverity::cases(), 'value'))],
            'location_description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'weather_conditions' => ['nullable', 'string', 'max:100'],
            'road_conditions' => ['nullable', 'string', 'max:100'],
            'traffic_conditions' => ['nullable', 'string', 'max:100'],
            'fault_determination' => ['nullable', 'string', Rule::in(array_column(FaultDetermination::cases(), 'value'))],
            'police_attended' => ['nullable', 'boolean'],
            'police_reference' => ['nullable', 'string', 'max:100'],
            'injuries_reported' => ['nullable', 'boolean'],
            'injury_count' => ['nullable', 'integer', 'min:0'],
            'third_party_involved' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'initial_assessment' => ['nullable', 'string'],
            'estimated_damage_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'vehicle_driveable' => ['nullable', 'boolean'],
            'recovery_required' => ['nullable', 'boolean'],
            'recovery_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(array_column(IncidentStatus::cases(), 'value'))],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:204800'], // 200 MB per file
        ];
    }
}
