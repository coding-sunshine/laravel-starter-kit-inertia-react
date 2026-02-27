<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTxrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'txr_start_time' => ['required', 'date', 'before_or_equal:txr_end_time'],
            'txr_end_time' => ['required', 'date', 'after_or_equal:txr_start_time'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'txr_start_time.required' => 'TXR start time is required.',
            'txr_start_time.date' => 'TXR start time must be a valid date.',
            'txr_start_time.before_or_equal' => 'TXR start time must be before or equal to end time.',
            'txr_end_time.required' => 'TXR end time is required.',
            'txr_end_time.date' => 'TXR end time must be a valid date.',
            'txr_end_time.after_or_equal' => 'TXR end time must be after or equal to start time.',
            'remarks.max' => 'Remarks must not exceed 1000 characters.',
        ];
    }
}
