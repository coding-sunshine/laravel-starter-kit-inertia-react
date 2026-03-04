<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleLease;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleLeasePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleLease $vehicleLease): bool
    {
        return $vehicleLease->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleLease $vehicleLease): bool
    {
        return $vehicleLease->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleLease $vehicleLease): bool
    {
        return $vehicleLease->organization_id === TenantContext::id();
    }

    public function restore(User $user, VehicleLease $vehicleLease): bool
    {
        return $vehicleLease->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, VehicleLease $vehicleLease): bool
    {
        return $vehicleLease->organization_id === TenantContext::id();
    }
}
