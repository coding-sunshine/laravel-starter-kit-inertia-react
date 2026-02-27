<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\User;
use App\Services\TenantContext;

final class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->organization_id === TenantContext::id();
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->organization_id === TenantContext::id();
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->organization_id === TenantContext::id();
    }
}
