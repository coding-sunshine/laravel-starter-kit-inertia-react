<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\DiverrtDestination;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * API-only RR upload validation (rake-linked). Does not extend {@see \App\Http\Requests\StoreRrUploadRequest}
 * because that class is {@see final}.
 */
final class StoreApiRailwayReceiptUploadRequest extends FormRequest
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
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'power_plant_id' => ['nullable', 'integer', 'exists:power_plants,id'],
            'diverrt_destination_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $rakeId = $this->input('rake_id');
                    if ($rakeId === null || $rakeId === '') {
                        $fail('A rake must be selected when uploading a diversion Railway Receipt.');

                        return;
                    }
                    $exists = DiverrtDestination::query()
                        ->where('id', (int) $value)
                        ->where('rake_id', (int) $rakeId)
                        ->exists();
                    if (! $exists) {
                        $fail('The selected diversion destination is invalid for this rake.');
                    }
                },
            ],
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
            'rake_id.required' => 'A rake must be selected.',
            'rake_id.exists' => 'Selected rake is invalid or no longer available.',
            'siding_id.exists' => 'Selected siding is invalid.',
            'power_plant_id.exists' => 'Selected power plant is invalid.',
        ];
    }
}
