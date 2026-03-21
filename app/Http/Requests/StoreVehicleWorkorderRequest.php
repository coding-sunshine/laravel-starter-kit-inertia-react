<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVehicleWorkorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sidingId = (int) $this->input('siding_id');

        return $this->user()?->canAccessSiding($sidingId) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'rcd_pin_no' => ['nullable', 'string', 'max:50'],
            'transport_name' => ['nullable', 'string', 'max:255'],
            'wo_no' => ['nullable', 'string', 'max:100'],
            'wo_no_2' => ['nullable', 'string', 'max:100'],
            'work_order_date' => ['nullable', 'date'],
            'issued_date' => ['nullable', 'date'],
            'proprietor_name' => ['nullable', 'string', 'max:255'],
            'represented_by' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'tyres' => ['nullable', 'integer', 'min:0'],
            'tare_weight' => ['nullable', 'numeric', 'min:0'],
            'mobile_no_1' => ['nullable', 'string', 'max:20'],
            'mobile_no_2' => ['nullable', 'string', 'max:20'],
            'owner_type' => ['nullable', 'string', 'max:50'],
            'regd_date' => ['nullable', 'date'],
            'permit_validity_date' => ['nullable', 'date'],
            'tax_validity_date' => ['nullable', 'date'],
            'fitness_validity_date' => ['nullable', 'date'],
            'insurance_validity_date' => ['nullable', 'date'],
            'maker_model' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'recommended_by' => ['nullable', 'string', 'max:255'],
            'referenced' => ['nullable', 'string', 'max:255'],
            'local_or_non_local' => ['nullable', 'string', 'max:50'],
            'pan_no' => ['nullable', 'string', 'max:50'],
            'gst_no' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'vehicle_no',
            'rcd_pin_no',
            'transport_name',
            'wo_no',
            'wo_no_2',
            'work_order_date',
            'issued_date',
            'proprietor_name',
            'represented_by',
            'place',
            'address',
            'tyres',
            'tare_weight',
            'mobile_no_1',
            'mobile_no_2',
            'owner_type',
            'regd_date',
            'permit_validity_date',
            'tax_validity_date',
            'fitness_validity_date',
            'insurance_validity_date',
            'maker_model',
            'make',
            'model',
            'remarks',
            'recommended_by',
            'referenced',
            'local_or_non_local',
            'pan_no',
            'gst_no',
        ];

        $data = $this->all();
        foreach ($nullable as $key) {
            if (isset($data[$key]) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $this->merge($data);
    }
}
