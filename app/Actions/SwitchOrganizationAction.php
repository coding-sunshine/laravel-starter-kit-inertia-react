<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;

final readonly class SwitchOrganizationAction
{
    /**
     * Switch the current tenant context to the given organization. Validates membership.
     */
    public function handle(User $user, Organization|int $organization): bool
    {
        return $user->switchOrganization($organization);
    }
}
