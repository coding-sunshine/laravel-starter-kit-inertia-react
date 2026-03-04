<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Location;
use App\Models\User;
use App\Services\TenantContext;

final class LocationPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Location $location): bool
    {
        return $location->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Location $location): bool
    {
        return $location->organization_id === TenantContext::id();
    }

    public function delete(User $user, Location $location): bool
    {
        return $location->organization_id === TenantContext::id();
    }

    public function restore(User $user, Location $location): bool
    {
        return $location->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return $location->organization_id === TenantContext::id();
    }
}
