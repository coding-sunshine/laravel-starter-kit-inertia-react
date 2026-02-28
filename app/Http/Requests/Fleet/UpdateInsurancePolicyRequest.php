<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\CoverageType;
use App\Enums\Fleet\InsurancePolicyStatus;
use App\Enums\Fleet\InsurancePolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('insurance_policy')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $policy = $this->route('insurance_policy');

        return [
            'policy_number' => ['required', 'string', 'max:100', Rule::unique('insurance_policies', 'policy_number')->ignore($policy->id)],
            'insurer_name' => ['required', 'string', 'max:200'],
            'policy_type' => ['required', 'string', Rule::in(array_column(InsurancePolicyType::cases(), 'value'))],
            'coverage_type' => ['required', 'string', Rule::in(array_column(CoverageType::cases(), 'value'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'premium_amount' => ['nullable', 'numeric', 'min:0'],
            'excess_amount' => ['nullable', 'numeric', 'min:0'],
            'no_claims_bonus_years' => ['nullable', 'integer', 'min:0'],
            'broker_name' => ['nullable', 'string', 'max:200'],
            'broker_contact' => ['nullable', 'string', 'max:200'],
            'auto_renewal' => ['nullable', 'boolean'],
            'status' => ['required', 'string', Rule::in(array_column(InsurancePolicyStatus::cases(), 'value'))],
        ];
    }
}
