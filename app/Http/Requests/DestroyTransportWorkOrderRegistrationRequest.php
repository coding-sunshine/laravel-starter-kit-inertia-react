<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TransportWorkOrderRegistration;
use Illuminate\Foundation\Http\FormRequest;

final class DestroyTransportWorkOrderRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->hasPermissionTo('sections.transport.update')) {
            return false;
        }

        $registration = $this->route('tw_registration');

        return $registration instanceof TransportWorkOrderRegistration;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
