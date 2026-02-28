<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ContractorComplianceStatus;
use App\Enums\Fleet\ContractorComplianceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContractorComplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\ContractorCompliance::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contractor_id' => ['required', 'integer', 'exists:contractors,id'],
            'compliance_type' => ['required', 'string', Rule::in(array_column(ContractorComplianceType::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(array_column(ContractorComplianceStatus::cases(), 'value'))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'document_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
