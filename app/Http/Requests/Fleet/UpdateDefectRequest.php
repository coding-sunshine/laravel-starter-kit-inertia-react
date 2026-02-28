<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\DefectCategory;
use App\Enums\Fleet\DefectSeverity;
use App\Enums\Fleet\DefectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDefectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('defect')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'defect_number' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', Rule::in(array_column(DefectCategory::cases(), 'value'))],
            'severity' => ['required', 'string', Rule::in(array_column(DefectSeverity::cases(), 'value'))],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'reported_by_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'reported_at' => ['required', 'date'],
            'location_on_vehicle' => ['nullable', 'string', 'max:200'],
            'status' => ['required', 'string', Rule::in(array_column(DefectStatus::cases(), 'value'))],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'resolution_description' => ['nullable', 'string'],
            'affects_roadworthiness' => ['nullable', 'boolean'],
            'affects_safety' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:204800'], // 200 MB per file
        ];
    }
}
