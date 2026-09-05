<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLoaderOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sid = $this->input('siding_id');
        if ($sid === '' || $sid === null) {
            $this->merge(['siding_id' => null]);
        }
    }
}
