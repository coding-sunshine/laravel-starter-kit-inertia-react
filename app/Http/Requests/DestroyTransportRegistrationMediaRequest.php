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
        if ($user === null || ! $user->hasPermissionTo('sections.transport.update')) {
            return false;
        }

        return $this->route('tw_registration') instanceof TransportWorkOrderRegistration;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
