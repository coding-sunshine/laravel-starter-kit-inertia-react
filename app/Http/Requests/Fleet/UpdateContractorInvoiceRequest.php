<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ContractorInvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContractorInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('contractor_invoice')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoice = $this->route('contractor_invoice');

        return [
            'contractor_id' => ['required', 'integer', 'exists:contractors,id'],
            'invoice_number' => ['required', 'string', 'max:100', Rule::unique('contractor_invoices', 'invoice_number')->where('organization_id', $invoice->organization_id)->ignore($invoice->id)],
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
