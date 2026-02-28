<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\VehicleCheckTemplateCheckType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleCheckTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\VehicleCheckTemplate::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50'],
            'check_type' => ['required', 'string', Rule::in(array_column(VehicleCheckTemplateCheckType::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:100'],
            'checklist' => ['nullable', 'array'],
            'checklist.*.label' => ['nullable', 'string'],
            'checklist.*.result_type' => ['nullable', 'string'],
            'workflow_route' => ['nullable', 'string', 'max:200'],
            'completion_percentage_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
