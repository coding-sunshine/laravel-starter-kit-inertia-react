<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DriverWorkingTime;
use App\Models\User;
use App\Services\TenantContext;

final class DriverWorkingTimePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DriverWorkingTime $driverWorkingTime): bool
    {
        return $driverWorkingTime->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, DriverWorkingTime $driverWorkingTime): bool
    {
        return $driverWorkingTime->organization_id === TenantContext::id();
    }

    public function delete(User $user, DriverWorkingTime $driverWorkingTime): bool
    {
        return $driverWorkingTime->organization_id === TenantContext::id();
    }

    public function restore(User $user, DriverWorkingTime $driverWorkingTime): bool
    {
        return $driverWorkingTime->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, DriverWorkingTime $driverWorkingTime): bool
    {
        return $driverWorkingTime->organization_id === TenantContext::id();
    }
}
