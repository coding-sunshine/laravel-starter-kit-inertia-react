<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PasswordResetOtpStatus;
use App\Exceptions\PasswordResetOtpException;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class ResetPasswordWithOtp
{
    public function handle(string $email, string $resetToken, #[SensitiveParameter] string $password): void
    {
        $record = PasswordResetOtp::findVerifiedForEmail($email);

        if ($record === null) {
            throw new PasswordResetOtpException(
                'reset_token_expired',
                __('The password reset session has expired. Please start again.'),
            );
        }

        if (! $record->isResetTokenValid()) {
            throw new PasswordResetOtpException(
                'reset_token_expired',
                __('The password reset session has expired. Please start again.'),
            );
        }

        if ($record->reset_token_hash === null || ! Hash::check($resetToken, $record->reset_token_hash)) {
            throw new PasswordResetOtpException(
                'reset_token_invalid',
                __('The password reset token is invalid.'),
            );
        }

        $user = User::query()->where('email', PasswordResetOtp::normalizeEmail($email))->first();

        if ($user === null) {
            throw new PasswordResetOtpException(
                'reset_token_invalid',
                __('The password reset token is invalid.'),
            );
        }

        $user->update([
            'password' => $password,
            'remember_token' => Str::random(60),
        ]);

        $record->update([
            'consumed_at' => now(),
            'status' => PasswordResetOtpStatus::Used,
        ]);

        $user->tokens()
            ->whereIn('name', ['mobile-access', 'mobile-refresh'])
            ->delete();

        event(new PasswordReset($user));
    }
}
