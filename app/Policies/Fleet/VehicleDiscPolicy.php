<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleDisc;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleDiscPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleDisc $vehicleDisc): bool
    {
        return $vehicleDisc->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleDisc $vehicleDisc): bool
    {
        return $vehicleDisc->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleDisc $vehicleDisc): bool
    {
        return $vehicleDisc->organization_id === TenantContext::id();
    }
}
