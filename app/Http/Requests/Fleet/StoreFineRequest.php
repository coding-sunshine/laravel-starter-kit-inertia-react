<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\FineStatus;
use App\Enums\Fleet\FineType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Fine::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'fine_type' => ['required', 'string', Rule::in(array_column(FineType::cases(), 'value'))],
            'offence_description' => ['nullable', 'string'],
            'offence_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'appeal_deadline' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(array_column(FineStatus::cases(), 'value'))],
            'appeal_notes' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:200'],
        ];
    }
}
