<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleTyre;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleTyrePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleTyre $vehicleTyre): bool
    {
        return $vehicleTyre->vehicle && $vehicleTyre->vehicle->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleTyre $vehicleTyre): bool
    {
        return $vehicleTyre->vehicle && $vehicleTyre->vehicle->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleTyre $vehicleTyre): bool
    {
        return $vehicleTyre->vehicle && $vehicleTyre->vehicle->organization_id === TenantContext::id();
    }
}
