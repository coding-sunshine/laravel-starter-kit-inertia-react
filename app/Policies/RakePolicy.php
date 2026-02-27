<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rake;
use App\Models\User;

final class RakePolicy
{
    /**
     * Determine if the user can view any rakes
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rakes:view');
    }

    /**
     * Determine if the user can view a specific rake
     */
    public function view(User $user, Rake $rake): bool
    {
        // Super admin can view any rake
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must have rakes:view permission
        if (! $user->hasPermissionTo('rakes:view')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($rake->siding_id);
    }

    /**
     * Determine if the user can create rakes
     */
    public function create(User $user): bool
    {
        // If tenant context is active, use organization-scoped permission check
        if (\App\Services\TenantContext::check()) {
            return $user->canInCurrentOrganization('rakes:create') || $user->hasPermissionTo('rakes:create');
        }

        return $user->hasPermissionTo('rakes:create');
    }

    /**
     * Determine if the user can update a specific rake
     */
    public function update(User $user, Rake $rake): bool
    {
        // Super admin can update any rake
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must have rakes:edit permission
        if (! $user->hasPermissionTo('rakes:edit')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($rake->siding_id);
    }

    /**
     * Determine if the user can delete a specific rake
     */
    public function delete(User $user, Rake $rake): bool
    {
        // Only super admin and siding_in_charge can delete rakes
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermissionTo('rakes:delete')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($rake->siding_id);
    }

    /**
     * Determine if the user can restore a deleted rake
     */
    public function restore(User $user, Rake $rake): bool
    {
        return $this->delete($user, $rake);
    }

    /**
     * Determine if the user can permanently delete a rake
     */
    public function forceDelete(User $user): bool
    {
        // Only super admin can force delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can assign wagons to a rake
     */
    public function assignWagons(User $user, Rake $rake): bool
    {
        // Must have update permission
        if (! $this->update($user, $rake)) {
            return false;
        }

        // Check if rake is in the correct state (pending, not yet departed)
        return in_array($rake->state, ['pending', 'loading', 'staged']);
    }

    /**
     * Determine if the user can mark rake as ready for departure
     */
    public function markReady(User $user, Rake $rake): bool
    {
        // Must have update permission
        if (! $this->update($user, $rake)) {
            return false;
        }

        // Rake must be in loading or staged state
        return in_array($rake->state, ['loading', 'staged']);
    }

    /**
     * Determine if the user can create a transaction receipt (TXR)
     */
    public function createTXR(User $user, Rake $rake): bool
    {
        // Must have update permission
        if (! $this->update($user, $rake)) {
            return false;
        }

        // Rake must be ready for TXR (staged state)
        return $rake->state === 'staged';
    }
}
