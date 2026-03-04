<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Garage;
use App\Models\User;
use App\Services\TenantContext;

final class GaragePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Garage $garage): bool
    {
        return $garage->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Garage $garage): bool
    {
        return $garage->organization_id === TenantContext::id();
    }

    public function delete(User $user, Garage $garage): bool
    {
        return $garage->organization_id === TenantContext::id();
    }

    public function restore(User $user, Garage $garage): bool
    {
        return $garage->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Garage $garage): bool
    {
        return $garage->organization_id === TenantContext::id();
    }
}
