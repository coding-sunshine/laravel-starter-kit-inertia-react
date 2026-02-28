<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\TrainingCourseCategory;
use App\Enums\Fleet\TrainingDeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTrainingCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\TrainingCourse::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_name' => ['required', 'string', 'max:200'],
            'course_code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(array_column(TrainingCourseCategory::cases(), 'value'))],
            'duration_hours' => ['required', 'numeric', 'min:0'],
            'delivery_method' => ['required', 'string', Rule::in(array_column(TrainingDeliveryMethod::cases(), 'value'))],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string'],
            'assessment_required' => ['boolean'],
            'pass_mark_percentage' => ['integer', 'min:0', 'max:100'],
            'certificate_awarded' => ['boolean'],
            'validity_period_months' => ['nullable', 'integer', 'min:0'],
            'cost_per_person' => ['nullable', 'numeric', 'min:0'],
            'provider_name' => ['nullable', 'string', 'max:200'],
            'provider_contact' => ['nullable', 'string', 'max:200'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'materials_required' => ['nullable', 'array'],
            'equipment_required' => ['nullable', 'array'],
            'is_mandatory' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
