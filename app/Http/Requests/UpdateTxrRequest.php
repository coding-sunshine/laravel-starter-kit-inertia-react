<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTxrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inspection_time' => ['required', 'date'],
            'inspection_end_time' => ['nullable', 'date', 'after_or_equal:inspection_time'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
