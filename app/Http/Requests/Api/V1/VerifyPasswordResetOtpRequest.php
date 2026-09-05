<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $otpLength = (int) config('auth.password_reset_otp.otp_length', 6);

        return [
            'email' => ['required', 'string', 'email', 'max:255', new ValidEmail],
            'otp' => [
                'required',
                'string',
                'digits:'.(string) $otpLength,
            ],
        ];
    }
}
