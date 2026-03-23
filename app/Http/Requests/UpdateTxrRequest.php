<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

final class UpdateTxrRequest extends FormRequest
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
            'inspection_time' => ['required', 'date'],
            'inspection_end_time' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $startRaw = (string) $this->input('inspection_time', '');
                    if ($startRaw === '') {
                        return;
                    }

                    try {
                        $startDate = Carbon::parse($startRaw)->toDateString();
                        $endDate = Carbon::parse((string) $value)->toDateString();
                    } catch (Throwable) {
                        return;
                    }

                    if ($endDate < $startDate) {
                        $fail('The inspection end time field must be a date after or equal to inspection time.');
                    }
                },
            ],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
