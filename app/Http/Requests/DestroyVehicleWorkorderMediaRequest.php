<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\VehicleWorkorder;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyVehicleWorkorderMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $workorder = $this->route('vehicle_workorder');

        return $workorder instanceof VehicleWorkorder
            && $user->canAccessSiding((int) $workorder->siding_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
