<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Mention;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class MentionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Mention $mention) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mentionable_type' => $this->mention->mentionable_type,
            'mentionable_id' => $this->mention->mentionable_id,
            'mentioned_by' => $this->mention->mentioned_by_user_id,
            'context' => $this->mention->context,
            'message' => 'You were mentioned in a note',
        ];
    }
}
