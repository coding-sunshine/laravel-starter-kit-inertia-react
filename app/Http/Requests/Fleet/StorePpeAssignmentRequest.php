<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\PpeAssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePpeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\PpeAssignment::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'ppe_type' => ['required', 'string', 'max:100'],
            'item_reference' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['required', 'date'],
            'expiry_or_return_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_column(PpeAssignmentStatus::cases(), 'value'))],
        ];
    }
}
