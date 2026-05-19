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
            'page' => ['nullable', 'integer', 'min:1'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'transport_name' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'regd_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $keys = [
            'view',
            'page',
            'siding_id',
            'transport_name',
            'vehicle_no',
            'regd_date',
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
