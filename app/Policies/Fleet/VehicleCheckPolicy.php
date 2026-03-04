<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleCheck;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleCheckPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleCheck $vehicleCheck): bool
    {
        return $vehicleCheck->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleCheck $vehicleCheck): bool
    {
        return $vehicleCheck->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleCheck $vehicleCheck): bool
    {
        return $vehicleCheck->organization_id === TenantContext::id();
    }
}
