<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrganizationInvitationSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation,
        public Organization $organization,
        public string $email,
        public string $role,
        public User $invitedBy
    ) {}
}
