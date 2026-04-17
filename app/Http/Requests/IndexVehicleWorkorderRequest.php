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
            'view' => ['nullable', 'string', 'in:vehicles,transporters'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'wo_no' => ['nullable', 'string', 'max:100'],
            'wo_no_2' => ['nullable', 'string', 'max:100'],
            'transport_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'mobile_no_1' => ['nullable', 'string', 'max:20'],
            'mobile_no_2' => ['nullable', 'string', 'max:20'],
            'model' => ['nullable', 'string', 'max:100'],
            'work_order_date' => ['nullable', 'date'],
            'issued_date' => ['nullable', 'date'],
            'proprietor_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'owner_type' => ['nullable', 'string', 'max:100'],
            'pan_no' => ['nullable', 'string', 'max:20'],
            'gst_no' => ['nullable', 'string', 'max:32'],
            'min_vehicles' => ['nullable', 'integer', 'min:0'],
            'max_vehicles' => ['nullable', 'integer', 'min:0'],
            'regd_date' => ['nullable', 'date'],
            'permit_validity_date' => ['nullable', 'date'],
            'tax_validity_date' => ['nullable', 'date'],
            'insurance_validity_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $keys = [
            'view',
            'siding_id',
            'vehicle_no',
            'wo_no',
            'wo_no_2',
            'transport_name',
            'mobile',
            'mobile_no_1',
            'mobile_no_2',
            'model',
            'work_order_date',
            'issued_date',
            'proprietor_name',
            'address',
            'owner_type',
            'pan_no',
            'gst_no',
            'min_vehicles',
            'max_vehicles',
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

        if (! array_key_exists('view', $data) || $data['view'] === null) {
            $data['view'] = 'vehicles';
        }

        $this->merge($data);
    }
}
