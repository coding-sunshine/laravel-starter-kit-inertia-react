<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\FuelTransactionMethod;
use App\Enums\Fleet\FuelTransactionValidationStatus;
use App\Enums\Fleet\FuelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFuelTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('fuel_transaction')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'fuel_card_id' => ['required', 'integer', 'exists:fuel_cards,id'],
            'transaction_timestamp' => ['required', 'date'],
            'fuel_station_id' => ['nullable', 'integer', 'exists:fuel_stations,id'],
            'fuel_station_name' => ['nullable', 'string', 'max:200'],
            'fuel_station_address' => ['nullable', 'string'],
            'fuel_type' => ['required', 'string', Rule::in(array_column(FuelType::cases(), 'value'))],
            'litres' => ['nullable', 'numeric', 'min:0'],
            'price_per_litre' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'pump_number' => ['nullable', 'string', 'max:10'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'transaction_method' => ['nullable', 'string', Rule::in(array_column(FuelTransactionMethod::cases(), 'value'))],
            'validation_status' => ['nullable', 'string', Rule::in(array_column(FuelTransactionValidationStatus::cases(), 'value'))],
        ];
    }
}
