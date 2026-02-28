<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ToolboxTalkStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateToolboxTalkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('toolbox_talk')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'presenter_id' => ['nullable', 'integer', 'exists:users,id'],
            'topic' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:200'],
            'attendee_driver_ids' => ['nullable', 'array'],
            'attendee_driver_ids.*' => ['integer', 'exists:drivers,id'],
            'attendee_user_ids' => ['nullable', 'array'],
            'attendee_user_ids.*' => ['integer', 'exists:users,id'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(array_column(ToolboxTalkStatus::cases(), 'value'))],
        ];
    }
}
