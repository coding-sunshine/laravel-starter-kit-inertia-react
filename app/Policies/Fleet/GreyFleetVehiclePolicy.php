<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\GreyFleetVehicle;
use App\Models\User;
use App\Services\TenantContext;

final class GreyFleetVehiclePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, GreyFleetVehicle $greyFleetVehicle): bool
    {
        return $greyFleetVehicle->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, GreyFleetVehicle $greyFleetVehicle): bool
    {
        return $greyFleetVehicle->organization_id === TenantContext::id();
    }

    public function delete(User $user, GreyFleetVehicle $greyFleetVehicle): bool
    {
        return $greyFleetVehicle->organization_id === TenantContext::id();
    }
}
