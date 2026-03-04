<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDvirCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\VehicleCheck::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'vehicle_check_template_id' => ['required', 'integer', 'exists:vehicle_check_templates,id'],
            'check_date' => ['required', 'date'],
            'performed_by_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'performed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'defect_id' => ['nullable', 'integer', 'exists:defects,id'],
            'items' => ['required', 'array'],
            'items.*.item_index' => ['required', 'integer', 'min:0'],
            'items.*.label' => ['required', 'string', 'max:500'],
            'items.*.result_type' => ['required', 'string', 'in:pass_fail,value,photo'],
            'items.*.result' => ['nullable', 'string', 'max:50'],
            'items.*.value_text' => ['nullable', 'string', 'max:500'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
