<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

final class SlackCriticalAlertNotification extends Notification
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $level = 'error',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $message = (new SlackMessage)
            ->error()
            ->content($this->title)
            ->attachment(function (SlackAttachment $attachment): void {
                $attachment->title($this->title)
                    ->content($this->body);
            });

        if ($this->level === 'warning') {
            $message->warning();
        }

        return $message;
    }
}
