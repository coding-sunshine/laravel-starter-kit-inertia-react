<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ContractorInvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContractorInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\ContractorInvoice::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contractor_id' => ['required', 'integer', 'exists:contractors,id'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(array_column(ContractorInvoiceStatus::cases(), 'value'))],
            'work_order_reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'paid_date' => ['nullable', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
