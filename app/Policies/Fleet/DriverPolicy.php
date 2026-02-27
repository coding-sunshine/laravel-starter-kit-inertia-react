<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Driver;
use App\Models\User;
use App\Services\TenantContext;

final class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Driver $driver): bool
    {
        return $driver->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Driver $driver): bool
    {
        return $driver->organization_id === TenantContext::id();
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $driver->organization_id === TenantContext::id();
    }

    public function restore(User $user, Driver $driver): bool
    {
        return $driver->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Driver $driver): bool
    {
        return $driver->organization_id === TenantContext::id();
    }
}
