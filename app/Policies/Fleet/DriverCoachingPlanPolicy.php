<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DriverCoachingPlan;
use App\Models\User;
use App\Services\TenantContext;

final class DriverCoachingPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DriverCoachingPlan $driverCoachingPlan): bool
    {
        return $driverCoachingPlan->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, DriverCoachingPlan $driverCoachingPlan): bool
    {
        return $driverCoachingPlan->organization_id === TenantContext::id();
    }

    public function delete(User $user, DriverCoachingPlan $driverCoachingPlan): bool
    {
        return $driverCoachingPlan->organization_id === TenantContext::id();
    }
}
