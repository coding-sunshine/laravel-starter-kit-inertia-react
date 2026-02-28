<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\RiskAssessmentStatus;
use App\Enums\Fleet\RiskAssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRiskAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('risk_assessment')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string', 'max:100'],
            'subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', Rule::in(array_column(RiskAssessmentType::cases(), 'value'))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'hazards' => ['nullable', 'string'],
            'control_measures' => ['nullable', 'string'],
            'risk_matrix' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(array_column(RiskAssessmentStatus::cases(), 'value'))],
            'review_date' => ['nullable', 'date'],
        ];
    }
}
