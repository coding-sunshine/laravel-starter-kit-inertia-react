<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\CostAllocationApprovalStatus;
use App\Enums\Fleet\CostAllocationSourceType;
use App\Enums\Fleet\CostAllocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCostAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\CostAllocation::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cost_center_id' => ['required', 'integer', 'exists:cost_centers,id'],
            'allocation_date' => ['required', 'date'],
            'cost_type' => ['required', 'string', Rule::in(array_column(CostAllocationType::cases(), 'value'))],
            'source_type' => ['required', 'string', Rule::in(array_column(CostAllocationSourceType::cases(), 'value'))],
            'source_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric'],
            'vat_amount' => ['numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_name' => ['nullable', 'string', 'max:200'],
            'approval_status' => ['required', 'string', Rule::in(array_column(CostAllocationApprovalStatus::cases(), 'value'))],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
        ];
    }
}
