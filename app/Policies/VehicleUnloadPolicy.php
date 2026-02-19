<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleUnload;

final class VehicleUnloadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VehicleUnload $vehicleUnload): bool
    {
        return $user->isSuperAdmin() || $user->canAccessSiding($vehicleUnload->siding_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VehicleUnload $vehicleUnload): bool
    {
        return $user->isSuperAdmin() || $user->canAccessSiding($vehicleUnload->siding_id);
    }

    public function delete(User $user, VehicleUnload $vehicleUnload): bool
    {
        return $user->isSuperAdmin() || $user->canAccessSiding($vehicleUnload->siding_id);
    }

    public function restore(User $user, VehicleUnload $vehicleUnload): bool
    {
        return $this->delete($user, $vehicleUnload);
    }

    public function forceDelete(User $user, VehicleUnload $vehicleUnload): bool
    {
        return $user->isSuperAdmin();
    }
}
