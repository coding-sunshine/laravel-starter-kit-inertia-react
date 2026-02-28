<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StorePartsInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\PartsInventory::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'garage_id' => ['nullable', 'integer', 'exists:garages,id'],
            'part_number' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reorder_cost' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:200'],
            'supplier_id' => ['nullable', 'integer', 'exists:parts_suppliers,id'],
        ];
    }
}
