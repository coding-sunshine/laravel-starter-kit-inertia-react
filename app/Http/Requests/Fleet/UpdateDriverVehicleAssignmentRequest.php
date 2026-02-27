<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDriverVehicleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('driver_vehicle_assignment')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignment_type' => ['required', 'string', Rule::enum(AssignmentType::class)],
            'assigned_date' => ['required', 'date'],
            'unassigned_date' => ['nullable', 'date', 'after_or_equal:assigned_date'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
