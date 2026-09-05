<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreSidingPreIndentReportRequest extends FormRequest
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $sid = $v->getData()['siding_id'] ?? null;
            if ($sid === null) {
                return;
            }
            /** @var User $user */
            $user = $this->user();
            if (! $user->canAccessSiding((int) $sid)) {
                $v->errors()->add('siding_id', __('You do not have access to this siding.'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('siding_id') === '' || $this->input('siding_id') === null) {
            $this->merge(['siding_id' => null]);
        }
    }
}
