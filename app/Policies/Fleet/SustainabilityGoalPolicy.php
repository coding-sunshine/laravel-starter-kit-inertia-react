<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\SustainabilityGoal;
use App\Models\User;
use App\Services\TenantContext;

final class SustainabilityGoalPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, SustainabilityGoal $goal): bool
    {
        return $goal->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, SustainabilityGoal $goal): bool
    {
        return $goal->organization_id === TenantContext::id();
    }

    public function delete(User $user, SustainabilityGoal $goal): bool
    {
        return $goal->organization_id === TenantContext::id();
    }
}
