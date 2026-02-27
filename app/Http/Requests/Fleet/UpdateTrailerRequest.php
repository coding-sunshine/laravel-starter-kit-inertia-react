<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTrailerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('trailer')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registration' => ['nullable', 'string', 'max:50'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:flatbed,box,tank,refrigerated,lowloader,other'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ];
    }
}

