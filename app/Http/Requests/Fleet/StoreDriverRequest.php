<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Driver::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:50'],
            'license_expiry_date' => ['required', 'date'],
            'license_status' => ['required', 'string', 'in:valid,expired,suspended,revoked'],
            'status' => ['required', 'string', 'in:active,suspended,terminated,on_leave'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
            'risk_category' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ];
    }
}

