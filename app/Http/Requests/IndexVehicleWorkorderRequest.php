<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IndexVehicleWorkorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'wo_no' => ['nullable', 'string', 'max:100'],
            'transport_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'model' => ['nullable', 'string', 'max:100'],
            'regd_date' => ['nullable', 'date'],
            'permit_validity_date' => ['nullable', 'date'],
            'tax_validity_date' => ['nullable', 'date'],
            'insurance_validity_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $keys = [
            'siding_id',
            'vehicle_no',
            'wo_no',
            'transport_name',
            'mobile',
            'model',
            'regd_date',
            'permit_validity_date',
            'tax_validity_date',
            'insurance_validity_date',
        ];

        $data = $this->all();
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $this->merge($data);
    }
}
