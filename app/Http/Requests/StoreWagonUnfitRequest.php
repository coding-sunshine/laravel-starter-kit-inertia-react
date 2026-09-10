<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWagonUnfitRequest extends FormRequest
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
            'unfit_wagons' => ['required', 'array', 'min:1'],
            'unfit_wagons.*.wagon_id' => ['required', 'integer', 'exists:wagons,id'],
            'unfit_wagons.*.reason_unfit' => ['required', 'string', 'max:1000'],
            'unfit_wagons.*.marked_by' => ['required', 'string', 'max:255'],
            'unfit_wagons.*.marking_method' => ['required', 'string', 'in:flag,light'],
            'unfit_wagons.*.marked_at' => ['required', 'date'],
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
            'unfit_wagons.required' => 'At least one unfit wagon record is required.',
            'unfit_wagons.array' => 'Unfit wagons must be an array.',
            'unfit_wagons.min' => 'At least one unfit wagon record is required.',
            'unfit_wagons.*.wagon_id.required' => 'Wagon selection is required.',
            'unfit_wagons.*.wagon_id.exists' => 'Selected wagon is invalid.',
            'unfit_wagons.*.reason_unfit.required' => 'Reason for unfit is required.',
            'unfit_wagons.*.reason_unfit.max' => 'Reason must not exceed 1000 characters.',
            'unfit_wagons.*.marked_by.required' => 'Marked by field is required.',
            'unfit_wagons.*.marked_by.max' => 'Marked by must not exceed 255 characters.',
            'unfit_wagons.*.marking_method.required' => 'Marking method is required.',
            'unfit_wagons.*.marking_method.in' => 'Marking method must be either flag or light.',
            'unfit_wagons.*.marked_at.required' => 'Marked time is required.',
            'unfit_wagons.*.marked_at.date' => 'Marked time must be a valid date.',
        ];
    }
}
