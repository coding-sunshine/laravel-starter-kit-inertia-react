<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

use function getPermissionsTeamId;
use function setPermissionsTeamId;

final class OrganizationPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->belongsToOrganization($organization->id)) {
            return true;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Only superadmins may create organisations via the UI.
     * Self-service subscribers get an org auto-provisioned via SignupController::provision().
     */
    public function create(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isOwnerSuperAdminOrRole($user, $organization, 'admin');
    }

    public function delete(User $user, Organization $organization): bool
    {
        if ($organization->isOwner($user)) {
            return true;
        }

        return $user->isSuperAdmin();
    }

    public function restore(User $user, Organization $organization): bool
    {
        if ($organization->isOwner($user)) {
            return true;
        }

        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function addMember(User $user, Organization $organization): bool
    {
        return $this->isOwnerSuperAdminOrRole($user, $organization, 'admin');
    }

    private function isOwnerSuperAdminOrRole(User $user, Organization $organization, string $role): bool
    {
        if ($organization->isOwner($user)) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->userHasOrgRole($user, $organization, $role);
    }

    private function userHasOrgRole(User $user, Organization $organization, string $role): bool
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($organization->id);
        try {
            return $user->hasRole($role);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
