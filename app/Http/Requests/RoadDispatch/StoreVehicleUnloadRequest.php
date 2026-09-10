<?php

declare(strict_types=1);

namespace App\Http\Requests\RoadDispatch;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVehicleUnloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sidingId = (int) $this->input('siding_id');
        if ($sidingId === 0) {
            return true;
        }

        return $this->user()?->canAccessSiding($sidingId) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'arrival_id' => ['required', 'integer', 'exists:vehicle_arrivals,id'],
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'jimms_challan_number' => ['nullable', 'string', 'max:30'],
            'arrival_time' => ['required', 'date'],
            'shift' => ['nullable', 'string', 'in:morning,evening,night'],
            'mine_weight_mt' => ['nullable', 'numeric', 'min:0'],
            'weighment_weight_mt' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
