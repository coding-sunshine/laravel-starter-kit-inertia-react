<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SearchUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view users') ?? false;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'filters.name' => ['sometimes', 'string', 'max:255'],
            'filters.email' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'regex:/^-?(id|name|email|created_at|updated_at)$/'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'include' => ['sometimes', 'array'],
            'include.*' => ['string', 'in:roles'],
        ];
    }
}
