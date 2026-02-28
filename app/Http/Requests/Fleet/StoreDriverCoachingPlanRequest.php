<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\DriverCoachingPlanStatus;
use App\Enums\Fleet\DriverCoachingPlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDriverCoachingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\DriverCoachingPlan::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'plan_type' => ['required', 'string', Rule::in(array_column(DriverCoachingPlanType::cases(), 'value'))],
            'title' => ['nullable', 'string', 'max:200'],
            'objectives' => ['nullable', 'string'],
            'objectives_json' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(array_column(DriverCoachingPlanStatus::cases(), 'value'))],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'assigned_coach_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
