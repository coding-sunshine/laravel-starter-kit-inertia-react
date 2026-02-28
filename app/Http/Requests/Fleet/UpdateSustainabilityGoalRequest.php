<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\SustainabilityGoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSustainabilityGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('sustainability_goal')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_column(SustainabilityGoalStatus::cases(), 'value'))],
            'target_date' => ['nullable', 'date'],
            'target_value' => ['nullable', 'numeric'],
            'target_unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}
