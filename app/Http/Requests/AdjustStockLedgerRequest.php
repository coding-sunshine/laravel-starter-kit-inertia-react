<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdjustStockLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'direction' => ['required', 'string', 'in:add,deduct'],
            'quantity_mt' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'remarks' => ['required', 'string', 'max:2000'],
        ];
    }
}
