<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\TrainingSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\TrainingSession::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_course_id' => ['required', 'integer', 'exists:training_courses,id'],
            'session_name' => ['required', 'string', 'max:200'],
            'instructor_name' => ['nullable', 'string', 'max:200'],
            'instructor_contact' => ['nullable', 'string', 'max:200'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:300'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registered_count' => ['integer', 'min:0'],
            'attended_count' => ['integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(array_column(TrainingSessionStatus::cases(), 'value'))],
            'completion_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'average_score' => ['nullable', 'numeric', 'min:0'],
            'feedback_score' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'materials_provided' => ['nullable', 'array'],
            'materials_provided.*' => ['string'],
        ];
    }
}
