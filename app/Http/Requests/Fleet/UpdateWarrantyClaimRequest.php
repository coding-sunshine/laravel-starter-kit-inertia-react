<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\WarrantyClaimStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWarrantyClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('warranty_claim')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'work_order_id' => ['required', 'integer', 'exists:work_orders,id'],
            'claim_number' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(array_column(WarrantyClaimStatus::cases(), 'value'))],
            'claim_amount' => ['nullable', 'numeric', 'min:0'],
            'settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'submitted_date' => ['nullable', 'date'],
            'settled_at' => ['nullable', 'date'],
        ];
    }
}
