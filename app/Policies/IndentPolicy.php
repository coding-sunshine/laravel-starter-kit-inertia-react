<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Indent;
use App\Models\User;

final class IndentPolicy
{
    /**
     * Determine if the user can view any indents
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('indents:view');
    }

    /**
     * Determine if the user can view a specific indent
     */
    public function view(User $user, Indent $indent): bool
    {
        // Super admin can view any indent
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must have indents:view permission
        if (! $user->hasPermissionTo('indents:view')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($indent->siding_id);
    }

    /**
     * Determine if the user can create indents
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('indents:create');
    }

    /**
     * Determine if the user can update a specific indent
     */
    public function update(User $user, Indent $indent): bool
    {
        // Super admin can update any indent
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must have indents:edit permission
        if (! $user->hasPermissionTo('indents:edit')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($indent->siding_id);
    }

    /**
     * Determine if the user can delete a specific indent
     */
    public function delete(User $user, Indent $indent): bool
    {
        // Only super admin and siding_in_charge can delete indents
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermissionTo('indents:delete')) {
            return false;
        }

        // Check siding-level access
        return $user->canAccessSiding($indent->siding_id);
    }

    /**
     * Determine if the user can restore a deleted indent
     */
    public function restore(User $user, Indent $indent): bool
    {
        return $this->delete($user, $indent);
    }

    /**
     * Determine if the user can permanently delete an indent
     */
    public function forceDelete(User $user, Indent $indent): bool
    {
        // Only super admin can force delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can approve an indent
     */
    public function approve(User $user, Indent $indent): bool
    {
        // Must have update permission
        if (! $this->update($user, $indent)) {
            return false;
        }

        // Check if indent is in pending state
        return $indent->status === 'pending';
    }

    /**
     * Determine if the user can reject an indent
     */
    public function reject(User $user, Indent $indent): bool
    {
        // Must have update permission
        if (! $this->update($user, $indent)) {
            return false;
        }

        // Check if indent is in pending state
        return $indent->status === 'pending';
    }

    /**
     * Determine if the user can fulfill/load an indent
     */
    public function fulfill(User $user, Indent $indent): bool
    {
        // Must have update permission
        if (! $this->update($user, $indent)) {
            return false;
        }

        // Check if indent is approved and not yet fulfilled
        return in_array($indent->status, ['approved', 'partial']);
    }

    /**
     * Determine if the user can close an indent
     */
    public function close(User $user, Indent $indent): bool
    {
        // Must have update permission
        if (! $this->update($user, $indent)) {
            return false;
        }

        // Check if indent is fulfilled
        return $indent->status === 'fulfilled';
    }
}
