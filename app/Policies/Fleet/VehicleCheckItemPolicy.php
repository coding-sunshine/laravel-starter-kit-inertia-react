<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleCheckItem;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleCheckItemPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleCheckItem $vehicleCheckItem): bool
    {
        return $vehicleCheckItem->vehicleCheck->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleCheckItem $vehicleCheckItem): bool
    {
        return $vehicleCheckItem->vehicleCheck->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleCheckItem $vehicleCheckItem): bool
    {
        return $vehicleCheckItem->vehicleCheck->organization_id === TenantContext::id();
    }
}
