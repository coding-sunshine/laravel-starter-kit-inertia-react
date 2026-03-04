<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkDocumentUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,txt|max:10240', // 10MB max per file
            'processing_type' => 'required|string|in:auto,project,lot',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Please select at least one file to upload.',
            'files.min' => 'Please select at least one file to upload.',
            'files.max' => 'You can upload a maximum of 20 files at once.',
            'files.*.required' => 'Each file must be a valid file.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.mimes' => 'Only PDF, image, Word document, and text files are allowed.',
            'files.*.max' => 'Each file must be smaller than 10MB.',
            'processing_type.required' => 'Please select a processing type.',
            'processing_type.in' => 'Invalid processing type selected.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'files' => 'documents',
            'files.*' => 'document file',
            'processing_type' => 'processing type',
        ];
    }
}
