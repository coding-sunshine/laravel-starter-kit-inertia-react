<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOperatorLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('operator_licence')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_number' => ['required', 'string', 'max:50'],
            'license_type' => ['required', 'string', 'in:standard_national,standard_international,restricted'],
            'traffic_commissioner_area' => ['required', 'string', 'in:north_eastern,north_western,west_midlands,eastern,western,southern,scottish'],
            'issue_date' => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date'],
            'authorized_vehicles' => ['required', 'integer', 'min:0'],
            'authorized_trailers' => ['nullable', 'integer', 'min:0'],
            'operating_centres' => ['required', 'array'],
            'status' => ['required', 'string', 'in:active,suspended,revoked,surrendered,applied,pending_review'],
        ];
    }
}

