<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ComplianceEntityType;
use App\Enums\Fleet\ComplianceItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateComplianceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('compliance_item')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(array_column(ComplianceEntityType::cases(), 'value'))],
            'entity_id' => ['required', 'integer', 'min:1'],
            'compliance_type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'expiry_date' => ['required', 'date'],
            'issue_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(array_column(ComplianceItemStatus::cases(), 'value'))],
            'days_warning' => ['nullable', 'integer', 'min:0'],
            'legal_requirement' => ['nullable', 'boolean'],
            'renewal_required' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
