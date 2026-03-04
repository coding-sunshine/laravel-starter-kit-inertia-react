<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\FuelStation;
use App\Models\User;
use App\Services\TenantContext;

final class FuelStationPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, FuelStation $fuelStation): bool
    {
        if ($fuelStation->organization_id === TenantContext::id()) {
            return true;
        }

        return $fuelStation->organization_id === null;
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, FuelStation $fuelStation): bool
    {
        return $fuelStation->organization_id === TenantContext::id();
    }

    public function delete(User $user, FuelStation $fuelStation): bool
    {
        return $fuelStation->organization_id === TenantContext::id();
    }
}
