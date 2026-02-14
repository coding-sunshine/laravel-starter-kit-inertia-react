<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\OrganizationInvitationAccepted;
use App\Events\OrganizationMemberAdded;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\InvitationAcceptedNotification;
use Throwable;

final readonly class AcceptOrganizationInvitationAction
{
    /**
     * Accept an invitation for the given user. Adds user to organization and marks invitation accepted.
     *
     * @throws Throwable If transaction fails
     */
    public function handle(OrganizationInvitation $invitation, User $user): OrganizationInvitation
    {
        $invitation->acceptForUser($user);

        $invitation->inviter->notify(new InvitationAcceptedNotification($invitation, $user));

        event(new OrganizationInvitationAccepted($invitation, $invitation->organization, $user, $invitation->role));
        event(new OrganizationMemberAdded($invitation->organization, $user, $invitation->role, $invitation->inviter));

        return $invitation;
    }
}
