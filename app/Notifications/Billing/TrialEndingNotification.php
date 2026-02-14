<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $planName,
        public readonly int $daysRemaining,
        public readonly ?DateTimeInterface $trialEndsAt = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->daysRemaining) {
            1 => 'Your trial ends tomorrow!',
            3 => 'Your trial ends in 3 days',
            7 => 'Your trial ends in 7 days',
            default => sprintf('Your trial ends in %d days', $this->daysRemaining),
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello!')
            ->line(sprintf('Your free trial of %s ends in %d days.', $this->planName, $this->daysRemaining))
            ->line('Add a payment method to continue enjoying all features after your trial ends.')
            ->action('Billing Dashboard', route('billing.index'))
            ->line('Questions? Our support team is happy to help.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_ending',
            'plan_name' => $this->planName,
            'days_remaining' => $this->daysRemaining,
            'trial_ends_at' => $this->trialEndsAt?->format('c'),
            'message' => sprintf('Your trial ends in %d days', $this->daysRemaining),
        ];
    }
}
