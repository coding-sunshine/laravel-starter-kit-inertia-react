<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\DriverQualificationStatus;
use App\Enums\Fleet\DriverQualificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDriverQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('driver_qualification')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'qualification_type' => ['required', 'string', Rule::in(array_column(DriverQualificationType::cases(), 'value'))],
            'qualification_name' => ['required', 'string', 'max:200'],
            'issuing_authority' => ['nullable', 'string', 'max:200'],
            'qualification_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', 'string', Rule::in(array_column(DriverQualificationStatus::cases(), 'value'))],
            'grade_achieved' => ['nullable', 'string', 'max:50'],
            'score_achieved' => ['nullable', 'integer', 'min:0', 'max:255'],
            'certificate_file_path' => ['nullable', 'string', 'max:500'],
            'verification_required' => ['boolean'],
            'verified_by' => ['nullable', 'integer', 'exists:users,id'],
            'verification_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
