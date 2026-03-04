<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\InsuranceClaim;
use App\Models\User;
use App\Services\TenantContext;

final class InsuranceClaimPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, InsuranceClaim $claim): bool
    {
        return $claim->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, InsuranceClaim $claim): bool
    {
        return $claim->organization_id === TenantContext::id();
    }

    public function delete(User $user, InsuranceClaim $claim): bool
    {
        return $claim->organization_id === TenantContext::id();
    }

    public function restore(User $user, InsuranceClaim $claim): bool
    {
        return $claim->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, InsuranceClaim $claim): bool
    {
        return $claim->organization_id === TenantContext::id();
    }
}
