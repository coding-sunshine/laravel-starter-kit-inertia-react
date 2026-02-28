<?php

declare(strict_types=1);

namespace App\Http\Requests\Fleet;

use App\Enums\Fleet\MileageClaimStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMileageClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('mileage_claim')) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grey_fleet_vehicle_id' => ['required', 'integer', 'exists:grey_fleet_vehicles,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'claim_date' => ['required', 'date'],
            'start_odometer' => ['nullable', 'integer', 'min:0'],
            'end_odometer' => ['nullable', 'integer', 'min:0'],
            'distance_km' => ['nullable', 'integer', 'min:0'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:200'],
            'amount_claimed' => ['nullable', 'numeric', 'min:0'],
            'amount_approved' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(array_column(MileageClaimStatus::cases(), 'value'))],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }
}
