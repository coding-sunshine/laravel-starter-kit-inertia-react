<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\User;
use App\Services\TenantContext;

final class DriverVehicleAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DriverVehicleAssignment $assignment): bool
    {
        return $assignment->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, DriverVehicleAssignment $assignment): bool
    {
        return $assignment->organization_id === TenantContext::id();
    }

    public function delete(User $user, DriverVehicleAssignment $assignment): bool
    {
        return $assignment->organization_id === TenantContext::id();
    }
}
