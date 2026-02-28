<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\SafetyObservationCategory;
use App\Enums\Fleet\SafetyObservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSafetyObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\SafetyObservation::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reported_by' => ['required', 'integer', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(array_column(SafetyObservationCategory::cases(), 'value'))],
            'location_description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(array_column(SafetyObservationStatus::cases(), 'value'))],
            'action_taken' => ['nullable', 'string'],
        ];
    }
}
