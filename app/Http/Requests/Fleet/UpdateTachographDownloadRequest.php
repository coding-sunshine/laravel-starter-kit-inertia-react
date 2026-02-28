<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\TachographDownloadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTachographDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tachograph_download')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'download_date' => ['required', 'date'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(array_column(TachographDownloadStatus::cases(), 'value'))],
            'analysed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
