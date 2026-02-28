<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\ReportFormat;
use App\Enums\Fleet\ReportScheduleFrequency;
use App\Enums\Fleet\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\Report::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'report_type' => ['required', 'string', Rule::in(array_column(ReportType::cases(), 'value'))],
            'parameters' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'schedule_enabled' => ['boolean'],
            'schedule_frequency' => ['required', 'string', Rule::in(array_column(ReportScheduleFrequency::cases(), 'value'))],
            'schedule_day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'schedule_day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'next_run_date' => ['nullable', 'date'],
            'recipients' => ['nullable', 'array'],
            'recipients.*' => ['string'],
            'format' => ['required', 'string', Rule::in(array_column(ReportFormat::cases(), 'value'))],
            'is_active' => ['boolean'],
        ];
    }
}
