<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\PoolVehicleBookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePoolVehicleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\PoolVehicleBooking::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'booking_start' => ['required', 'date'],
            'booking_end' => ['required', 'date', 'after_or_equal:booking_start'],
            'status' => ['nullable', 'string', Rule::in(array_column(PoolVehicleBookingStatus::cases(), 'value'))],
            'purpose' => ['nullable', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:200'],
            'odometer_start' => ['nullable', 'integer', 'min:0'],
            'odometer_end' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
