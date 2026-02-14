<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DunningFailedPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Organization $organization,
        public int $attemptNumber,
        public int $daysSinceFailure
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('dashboard');

        return (new MailMessage)
            ->subject('Payment Update Required')
            ->greeting("Hello {$notifiable->name},")
            ->line('We were unable to process a recent payment for your '.$this->organization->name.' account.')
            ->line("This is reminder #{$this->attemptNumber} (day {$this->daysSinceFailure} since the failure).")
            ->action('Update Payment Method', $url)
            ->line('Please update your payment method to avoid service interruption.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organization->id,
            'attempt_number' => $this->attemptNumber,
            'days_since_failure' => $this->daysSinceFailure,
        ];
    }
}
