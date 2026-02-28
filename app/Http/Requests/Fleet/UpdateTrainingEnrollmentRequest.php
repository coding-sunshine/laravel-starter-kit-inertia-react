<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\TrainingEnrollmentStatus;
use App\Enums\Fleet\TrainingPassFail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTrainingEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('training_enrollment')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'enrollment_date' => ['required', 'date'],
            'enrollment_status' => ['required', 'string', Rule::in(array_column(TrainingEnrollmentStatus::cases(), 'value'))],
            'attendance_marked' => ['boolean'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'completion_percentage' => ['integer', 'min:0', 'max:100'],
            'assessment_score' => ['nullable', 'integer', 'min:0', 'max:255'],
            'pass_fail' => ['required', 'string', Rule::in(array_column(TrainingPassFail::cases(), 'value'))],
            'certificate_issued' => ['boolean'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'feedback_rating' => ['nullable', 'integer', 'min:0', 'max:255'],
            'feedback_comments' => ['nullable', 'string'],
        ];
    }
}
