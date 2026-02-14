<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvitationAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly OrganizationInvitation $invitation,
        private readonly User $acceptedUser
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
        $organizationName = $this->invitation->organization->name;

        return (new MailMessage)
            ->subject(__(':name accepted the invitation to join :organization', [
                'name' => $this->acceptedUser->name,
                'organization' => $organizationName,
            ]))
            ->line(__(':name has accepted your invitation and joined **:organization**.', [
                'name' => $this->acceptedUser->name,
                'organization' => $organizationName,
            ]));
    }
}
