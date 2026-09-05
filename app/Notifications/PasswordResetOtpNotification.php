<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PasswordResetOtpNotification extends Notification
{
    public function __construct(
        private readonly string $otp,
        private readonly int $expiresInMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Password reset verification code'))
            ->line(__('You requested a password reset for your account.'))
            ->line(__('Your verification code is: :otp', ['otp' => $this->otp]))
            ->line(__('This code expires in :minutes minutes.', ['minutes' => $this->expiresInMinutes]))
            ->line(__('If you did not request a password reset, no action is required.'));
    }
}
