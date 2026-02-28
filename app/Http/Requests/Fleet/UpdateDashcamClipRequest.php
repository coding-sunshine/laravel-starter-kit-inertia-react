<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\DashcamClipEventType;
use App\Enums\Fleet\DashcamClipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDashcamClipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('dashcam_clip')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clip = $this->route('dashcam_clip');

        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,id'],
            'clip_id' => ['nullable', 'string', 'max:100', Rule::unique('dashcam_clips', 'clip_id')->ignore($clip->id)],
            'event_type' => ['required', 'string', Rule::in(array_column(DashcamClipEventType::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(array_column(DashcamClipStatus::cases(), 'value'))],
            'clip_url' => ['nullable', 'string', 'max:1000'],
            'thumbnail_url' => ['nullable', 'string', 'max:1000'],
            'recorded_at' => ['required', 'date'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'file_size_bytes' => ['nullable', 'integer', 'min:0'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
