<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PasswordResetOtpStatus;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Support\Facades\Hash;

final readonly class SendPasswordResetOtp
{
    public function handle(string $email): void
    {
        $normalizedEmail = PasswordResetOtp::normalizeEmail($email);

        $user = User::query()->where('email', $normalizedEmail)->first();

        if ($user === null) {
            return;
        }

        PasswordResetOtp::query()
            ->where('email', $normalizedEmail)
            ->where('status', PasswordResetOtpStatus::Pending)
            ->update(['status' => PasswordResetOtpStatus::Superseded]);

        $otpLength = (int) config('auth.password_reset_otp.otp_length', 6);
        $otp = mb_str_pad(
            (string) random_int(0, (10 ** $otpLength) - 1),
            $otpLength,
            '0',
            STR_PAD_LEFT,
        );

        $otpTtlMinutes = (int) config('auth.password_reset_otp.otp_ttl_minutes', 10);

        PasswordResetOtp::query()->create([
            'email' => $normalizedEmail,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes($otpTtlMinutes),
            'attempts' => 0,
            'status' => PasswordResetOtpStatus::Pending,
        ]);

        $user->notify(new PasswordResetOtpNotification($otp, $otpTtlMinutes));
    }
}
