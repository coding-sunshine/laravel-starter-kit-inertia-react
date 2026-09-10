<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Rake;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;

/**
 * API-only create e-Demand: same fields as {@see StoreIndentRequest}, but `pdf` is required
 * so the confirmation PDF is stored and available via download like the web PDF modal flow.
 */
final class StoreIndentApiRequest extends FormRequest
{
    /** @var list<string> */
    private const INDENT_STATE_VALUES = [
        'pending',
        'allocated',
        'partial',
        'completed',
        'cancelled',
        'historical_import',
        'submitted',
        'acknowledged',
        'fulfilled',
        'closed',
    ];

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
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['required'],
            'state' => ['nullable', 'string', Rule::in(self::INDENT_STATE_VALUES)],
            'remarks' => ['nullable', 'string', 'max:65535'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('indents', 'fnr_number')->whereNull('deleted_at'),
            ],
            'railway_reference_no' => ['nullable', 'string', 'max:100'],
            'destination' => [
                'required',
                'string',
                'max:100',
                Rule::exists('power_plants', 'code')->where('is_active', true),
            ],
            'expected_loading_date' => ['required', 'date'],
            'required_by_date' => ['nullable', 'date'],
            'indent_at' => ['required', 'date'],
            'demanded_stock' => ['required', 'string', 'max:100'],
            'total_units' => ['required', 'integer', 'min:1', 'max:999999'],
            'target_quantity_mt' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'allocated_quantity_mt' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'available_stock_mt' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'rake_serial_number' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;
                    if ($trimmed === null) {
                        return;
                    }

                    $sidingId = (int) $this->input('siding_id');
                    if ($sidingId <= 0) {
                        return;
                    }

                    $indentAt = $this->input('indent_at');
                    if ($indentAt === null || $indentAt === '') {
                        return;
                    }

                    $reference = Date::parse($indentAt);

                    $existsInMonth = Rake::query()
                        ->where('rake_serial_number', $trimmed)
                        ->where('siding_id', $sidingId)
                        ->whereYear('loading_date', $reference->year)
                        ->whereMonth('loading_date', $reference->month)
                        ->exists();

                    if ($existsInMonth) {
                        $fail('This rake number is already in use for this siding in the indent month.');
                    }
                },
            ],
            'rake_number' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;
                    if ($trimmed === null) {
                        return;
                    }

                    $sidingId = (int) $this->input('siding_id');
                    if ($sidingId <= 0) {
                        return;
                    }

                    $indentAt = $this->input('indent_at');
                    if ($indentAt === null || $indentAt === '') {
                        return;
                    }

                    $reference = Date::parse($indentAt);

                    $existsInMonth = Rake::query()
                        ->where('rake_number', $trimmed)
                        ->where('siding_id', $sidingId)
                        ->whereYear('loading_date', $reference->year)
                        ->whereMonth('loading_date', $reference->month)
                        ->exists();

                    if ($existsInMonth) {
                        $fail('This rake sequence is already in use for this siding in the indent month.');
                    }
                },
            ],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('indent_number') && is_string($this->input('indent_number'))) {
            $merged['indent_number'] = mb_trim($this->input('indent_number'));
        }

        if ($this->has('fnr_number')) {
            $raw = $this->input('fnr_number');
            $merged['fnr_number'] = is_string($raw) && mb_trim($raw) !== '' ? mb_trim($raw) : null;
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
