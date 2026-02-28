<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\WorkflowTriggerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkflowDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\WorkflowDefinition::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['required', 'string', Rule::in(array_column(WorkflowTriggerType::cases(), 'value'))],
            'trigger_config' => ['nullable', 'array'],
            'steps' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
