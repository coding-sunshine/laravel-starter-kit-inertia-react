<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TermsVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewTermsVersionPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TermsVersion $termsVersion
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('terms.accept');

        return (new MailMessage)
            ->subject(__('New :document require your acceptance', ['document' => $this->termsVersion->type->label()]))
            ->line(__('We have updated our :document.', ['document' => mb_strtolower($this->termsVersion->type->label())]))
            ->line($this->termsVersion->title)
            ->action(__('Review and accept'), $url)
            ->line(__('You will need to accept the new version to continue using the application.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'terms_version_id' => $this->termsVersion->id,
            'title' => $this->termsVersion->title,
            'type' => $this->termsVersion->type->value,
            'accept_url' => route('terms.accept'),
        ];
    }
}
