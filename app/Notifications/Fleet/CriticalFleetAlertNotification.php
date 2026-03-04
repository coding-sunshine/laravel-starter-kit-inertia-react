<?php

declare(strict_types=1);

namespace App\Notifications\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Organization;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CriticalFleetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Alert $alert,
        public Organization $organization,
    ) {}

    /**
     * @return array<int, string>
     */
    /**
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return '[Fleet] '.$this->alert->title.': '.$this->alert->description;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = url('/fleet');

        return (new MailMessage)
            ->subject('[Fleet] Critical: '.$this->alert->title)
            ->line($this->alert->description)
            ->action('View fleet', $dashboardUrl)
            ->line('Alert type: '.$this->alert->alert_type.' | Severity: '.$this->alert->severity);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'organization_id' => $this->organization->id,
            'alert_type' => $this->alert->alert_type,
            'severity' => $this->alert->severity,
            'title' => $this->alert->title,
            'description' => $this->alert->description,
            'url' => url('/fleet'),
        ];
    }
}
