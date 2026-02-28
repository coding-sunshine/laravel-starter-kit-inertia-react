<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\FuelCardStatus;
use App\Enums\Fleet\FuelCardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFuelCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fleet\FuelCard::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'max:50', 'unique:fuel_cards,card_number'],
            'provider' => ['required', 'string', 'max:100'],
            'card_type' => ['required', 'string', Rule::in(array_column(FuelCardType::cases(), 'value'))],
            'status' => ['required', 'string', Rule::in(array_column(FuelCardStatus::cases(), 'value'))],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'pin_required' => ['nullable', 'boolean'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'weekly_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'transaction_limit' => ['nullable', 'numeric', 'min:0'],
            'fuel_type_restrictions' => ['nullable', 'array'],
            'location_restrictions' => ['nullable', 'array'],
            'time_restrictions' => ['nullable', 'array'],
            'assigned_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'assigned_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
