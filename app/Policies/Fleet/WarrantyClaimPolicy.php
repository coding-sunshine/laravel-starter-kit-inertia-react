<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WarrantyClaim;
use App\Models\User;
use App\Services\TenantContext;

final class WarrantyClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WarrantyClaim $warrantyClaim): bool
    {
        return $warrantyClaim->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WarrantyClaim $warrantyClaim): bool
    {
        return $warrantyClaim->organization_id === TenantContext::id();
    }

    public function delete(User $user, WarrantyClaim $warrantyClaim): bool
    {
        return $warrantyClaim->organization_id === TenantContext::id();
    }

    public function restore(User $user, WarrantyClaim $warrantyClaim): bool
    {
        return $warrantyClaim->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, WarrantyClaim $warrantyClaim): bool
    {
        return $warrantyClaim->organization_id === TenantContext::id();
    }
}
