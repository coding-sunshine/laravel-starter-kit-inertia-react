<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\EvChargingStation;
use App\Models\User;
use App\Services\TenantContext;

final class EvChargingStationPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, EvChargingStation $evChargingStation): bool
    {
        return $evChargingStation->organization_id === TenantContext::id()
            || $evChargingStation->organization_id === null;
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, EvChargingStation $evChargingStation): bool
    {
        return $evChargingStation->organization_id === TenantContext::id();
    }

    public function delete(User $user, EvChargingStation $evChargingStation): bool
    {
        return $evChargingStation->organization_id === TenantContext::id();
    }

    public function restore(User $user, EvChargingStation $evChargingStation): bool
    {
        return $evChargingStation->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, EvChargingStation $evChargingStation): bool
    {
        return $evChargingStation->organization_id === TenantContext::id();
    }
}
