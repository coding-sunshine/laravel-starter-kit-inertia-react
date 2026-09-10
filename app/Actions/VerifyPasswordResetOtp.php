<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PasswordResetOtpStatus;
use App\Exceptions\PasswordResetOtpException;
use App\Models\PasswordResetOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class VerifyPasswordResetOtp
{
    public function handle(string $email, string $otp): string
    {
        $record = PasswordResetOtp::findPendingForEmail($email);

        if ($record === null) {
            throw new PasswordResetOtpException(
                'otp_expired',
                __('The verification code has expired. Please request a new one.'),
            );
        }

        if ($record->status === PasswordResetOtpStatus::Locked) {
            throw new PasswordResetOtpException(
                'otp_locked',
                __('Too many invalid attempts. Please request a new verification code.'),
            );
        }

        if ($record->expires_at->isPast()) {
            throw new PasswordResetOtpException(
                'otp_expired',
                __('The verification code has expired. Please request a new one.'),
            );
        }

        $maxAttempts = (int) config('auth.password_reset_otp.max_attempts', 5);

        if ($record->attempts >= $maxAttempts) {
            $record->update(['status' => PasswordResetOtpStatus::Locked]);

            throw new PasswordResetOtpException(
                'otp_locked',
                __('Too many invalid attempts. Please request a new verification code.'),
            );
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            if ($record->attempts >= $maxAttempts) {
                $record->update(['status' => PasswordResetOtpStatus::Locked]);

                throw new PasswordResetOtpException(
                    'otp_locked',
                    __('Too many invalid attempts. Please request a new verification code.'),
                );
            }

            throw new PasswordResetOtpException(
                'otp_invalid',
                __('The verification code is invalid.'),
            );
        }

        $resetToken = Str::random(64);
        $resetTokenTtlMinutes = (int) config('auth.password_reset_otp.reset_token_ttl_minutes', 30);

        $record->update([
            'verified_at' => now(),
            'reset_token_hash' => Hash::make($resetToken),
            'reset_token_expires_at' => now()->addMinutes($resetTokenTtlMinutes),
            'status' => PasswordResetOtpStatus::Verified,
        ]);

        return $resetToken;
    }
}
