<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\WorkOrderPriority;
use App\Enums\Fleet\WorkOrderStatus;
use App\Enums\Fleet\WorkOrderType;
use App\Enums\Fleet\WorkOrderUrgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('work_order')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'work_order_number' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'work_type' => ['required', 'string', Rule::in(array_column(WorkOrderType::cases(), 'value'))],
            'priority' => ['required', 'string', Rule::in(array_column(WorkOrderPriority::cases(), 'value'))],
            'status' => ['required', 'string', Rule::in(array_column(WorkOrderStatus::cases(), 'value'))],
            'urgency' => ['required', 'string', Rule::in(array_column(WorkOrderUrgency::cases(), 'value'))],
            'assigned_garage_id' => ['nullable', 'integer', 'exists:garages,id'],
            'assigned_technician' => ['nullable', 'string', 'max:200'],
            'scheduled_date' => ['nullable', 'date'],
            'started_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'mileage_at_start' => ['nullable', 'integer', 'min:0'],
            'mileage_at_completion' => ['nullable', 'integer', 'min:0'],
            'vehicle_off_road' => ['nullable', 'boolean'],
            'completion_notes' => ['nullable', 'string'],
        ];
    }
}
