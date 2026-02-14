<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

final class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly OrganizationInvitation $invitation
    ) {}

    public static function sendToEmail(OrganizationInvitation $invitation): void
    {
        NotificationFacade::route('mail', $invitation->email)
            ->notify(new self($invitation));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invitations.show', ['token' => $this->invitation->token]);
        $organizationName = $this->invitation->organization->name;
        $inviterName = $this->invitation->inviter->name ?? 'A team member';

        return (new MailMessage)
            ->subject(__('You have been invited to join :organization', ['organization' => $organizationName]))
            ->line(__(':inviter has invited you to join **:organization** as a **:role**.', [
                'inviter' => $inviterName,
                'organization' => $organizationName,
                'role' => $this->invitation->role,
            ]))
            ->action(__('Accept invitation'), $url)
            ->line(__('This invitation expires on :date.', ['date' => $this->invitation->expires_at->format('F j, Y')]));
    }
}
