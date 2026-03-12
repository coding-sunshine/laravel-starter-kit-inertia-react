<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRrUploadRequest extends FormRequest
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
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
            'siding_id' => ['required_without:rake_id', 'integer', 'exists:sidings,id'],
            'power_plant_id' => ['required_without:rake_id', 'integer', 'exists:power_plants,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => 'A PDF file is required.',
            'pdf.file' => 'The PDF must be a valid file.',
            'pdf.mimes' => 'The file must be a PDF.',
            'pdf.max' => 'The PDF must not exceed 10 MB.',
            'siding_id.required' => 'Siding is required.',
            'siding_id.exists' => 'Selected siding is invalid.',
            'power_plant_id.required' => 'Power plant is required.',
            'power_plant_id.exists' => 'Selected power plant is invalid.',
        ];
    }
}
