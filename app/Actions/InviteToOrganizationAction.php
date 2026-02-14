<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\OrganizationInvitationSent;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use InvalidArgumentException;

final readonly class InviteToOrganizationAction
{
    /**
     * Create an invitation and send the notification email.
     *
     * @throws InvalidArgumentException If role is not in ASSIGNABLE_ORG_ROLES
     */
    public function handle(Organization $organization, string $email, string $role, User $invitedBy): OrganizationInvitation
    {
        if (! in_array($role, Organization::ASSIGNABLE_ORG_ROLES, true)) {
            throw new InvalidArgumentException(sprintf("Invalid role '%s'. Must be one of: ", $role).implode(', ', Organization::ASSIGNABLE_ORG_ROLES));
        }

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy->id,
        ]);

        $existingUser = User::query()->where('email', $email)->first();
        if ($existingUser instanceof User) {
            $existingUser->notify(new OrganizationInvitationNotification($invitation));
        } else {
            OrganizationInvitationNotification::sendToEmail($invitation);
        }

        event(new OrganizationInvitationSent($invitation, $organization, $email, $role, $invitedBy));

        return $invitation;
    }
}
