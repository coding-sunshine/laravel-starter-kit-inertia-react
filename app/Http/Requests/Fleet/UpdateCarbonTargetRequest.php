<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\CarbonTargetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCarbonTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('carbon_target')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'period' => ['required', 'string', Rule::in(array_column(CarbonTargetPeriod::cases(), 'value'))],
            'target_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'target_co2_kg' => ['required', 'numeric', 'min:0'],
            'baseline_co2_kg' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
