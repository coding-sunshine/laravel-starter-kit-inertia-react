<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\InsurancePolicy;
use App\Models\User;
use App\Services\TenantContext;

final class InsurancePolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, InsurancePolicy $policy): bool
    {
        return $policy->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, InsurancePolicy $policy): bool
    {
        return $policy->organization_id === TenantContext::id();
    }

    public function delete(User $user, InsurancePolicy $policy): bool
    {
        return $policy->organization_id === TenantContext::id();
    }

    public function restore(User $user, InsurancePolicy $policy): bool
    {
        return $policy->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, InsurancePolicy $policy): bool
    {
        return $policy->organization_id === TenantContext::id();
    }
}
