<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\VehicleCheckItemResult;
use App\Enums\Fleet\VehicleCheckItemResultType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVehicleCheckItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vehicle_check_item')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'item_index' => ['nullable', 'integer', 'min:0'],
            'label' => ['required', 'string', 'max:500'],
            'result_type' => ['required', 'string', Rule::in(array_column(VehicleCheckItemResultType::cases(), 'value'))],
            'result' => ['nullable', 'string', Rule::in(array_column(VehicleCheckItemResult::cases(), 'value'))],
            'value_text' => ['nullable', 'string', 'max:500'],
            'photo_media_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
