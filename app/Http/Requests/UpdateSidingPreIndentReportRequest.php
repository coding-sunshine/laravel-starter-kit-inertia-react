<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSidingPreIndentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'report_date' => ['required', 'date'],
            'total_indent_raised' => ['required', 'integer', 'min:0'],
            'indent_available' => ['required', 'integer', 'min:0'],
            'loading_status_text' => ['nullable', 'string'],
            'indent_details_text' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('siding_id') === '' || $this->input('siding_id') === null) {
            $this->merge(['siding_id' => null]);
        }
    }
}
