<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\WorkshopBayStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkshopBayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\WorkshopBay::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(array_column(WorkshopBayStatus::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
