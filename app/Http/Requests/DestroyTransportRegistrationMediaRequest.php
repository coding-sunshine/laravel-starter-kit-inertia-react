<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TransportWorkOrderRegistration;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyTransportRegistrationMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $registration = $this->route('tw_registration');
        if (! $registration instanceof TransportWorkOrderRegistration) {
            return false;
        }

        $sidingId = $registration->siding_id;
        if ($sidingId === null) {
            return true;
        }

        return $user->canAccessSiding((int) $sidingId);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
