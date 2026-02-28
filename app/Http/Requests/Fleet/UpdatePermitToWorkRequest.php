<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\PermitToWorkStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePermitToWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('permit_to_work')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'issued_by' => ['required', 'integer', 'exists:users,id'],
            'issued_to' => ['nullable', 'integer', 'exists:users,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'permit_number' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'status' => ['nullable', 'string', Rule::in(array_column(PermitToWorkStatus::cases(), 'value'))],
            'conditions' => ['nullable', 'string'],
        ];
    }
}
