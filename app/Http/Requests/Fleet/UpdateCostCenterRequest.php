<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cost_center')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'parent_cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'cost_center_type' => ['required', 'string', 'in:department,project,location,vehicle_type'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'budget_annual' => ['nullable', 'numeric', 'min:0'],
            'budget_monthly' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

