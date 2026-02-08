<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Notifications\Notifiable;

/**
 * Notifiable that routes Slack notifications to the configured webhook URL.
 * Use with SlackCriticalAlertNotification when SLACK_WEBHOOK_URL is set.
 */
final class SlackWebhookRecipient
{
    use Notifiable;

    public function routeNotificationForSlack(): ?string
    {
        $url = config('services.slack.webhook_url');

        return $url !== null && $url !== '' ? $url : null;
    }
}
