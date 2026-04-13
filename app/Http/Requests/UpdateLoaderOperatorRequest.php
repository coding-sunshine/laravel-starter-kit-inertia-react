<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLoaderOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $loaderOperator = $this->route('loaderOperator');
        $id = is_object($loaderOperator) ? $loaderOperator->getKey() : $loaderOperator;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('loader_operators', 'name')->ignore($id),
            ],
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
