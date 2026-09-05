<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Siding;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class IndexSidingPreIndentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $accessibleSidingIds = $user->isSuperAdmin()
            ? Siding::query()->where('is_active', true)->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        return [
            'siding_id' => ['nullable', 'integer', Rule::in($accessibleSidingIds)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $v->getData();
            $from = $data['date_from'] ?? null;
            $to = $data['date_to'] ?? null;
            if ($from !== null && $to !== null && $from > $to) {
                $v->errors()->add('date_to', 'The end date must be on or after the start date.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $queryParams = $this->query->all();

        $siding = $this->input('siding_id');
        if ($siding === '' || $siding === null) {
            $this->merge(['siding_id' => null]);
        }
        foreach (['date_from', 'date_to'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }

        if (! array_key_exists('date_from', $queryParams) && ! array_key_exists('date_to', $queryParams)) {
            $today = now()->toDateString();
            $this->merge([
                'date_from' => $today,
                'date_to' => $today,
            ]);
        }
    }
}
