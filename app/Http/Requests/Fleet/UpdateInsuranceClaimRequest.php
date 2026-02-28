<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\InsuranceClaimStatus;
use App\Enums\Fleet\InsuranceClaimType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInsuranceClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('insurance_claim')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $claim = $this->route('insurance_claim');

        return [
            'incident_id' => ['required', 'integer', 'exists:incidents,id'],
            'insurance_policy_id' => ['required', 'integer', 'exists:insurance_policies,id'],
            'claim_number' => ['required', 'string', 'max:100', Rule::unique('insurance_claims', 'claim_number')->ignore($claim->id)],
            'claim_type' => ['required', 'string', Rule::in(array_column(InsuranceClaimType::cases(), 'value'))],
            'claim_amount' => ['nullable', 'numeric', 'min:0'],
            'excess_amount' => ['nullable', 'numeric', 'min:0'],
            'settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(array_column(InsuranceClaimStatus::cases(), 'value'))],
            'submitted_date' => ['nullable', 'date'],
            'acknowledged_date' => ['nullable', 'date'],
            'settlement_date' => ['nullable', 'date'],
            'claim_handler_name' => ['nullable', 'string', 'max:200'],
            'claim_handler_contact' => ['nullable', 'string', 'max:200'],
            'legal_action_required' => ['nullable', 'boolean'],
            'claim_narrative' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:204800'],
        ];
    }
}
