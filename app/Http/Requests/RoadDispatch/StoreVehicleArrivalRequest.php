<?php

declare(strict_types=1);

namespace App\Http\Requests\RoadDispatch;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVehicleArrivalRequest extends FormRequest
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
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'indent_id' => ['nullable', 'integer', 'exists:indents,id'],
            'arrived_at' => ['nullable', 'date'],
            'shift' => ['nullable', 'string', 'in:morning,evening,night'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'tare_weight' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
