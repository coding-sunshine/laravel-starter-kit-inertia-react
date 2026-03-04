<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\CostCenter;
use App\Models\User;
use App\Services\TenantContext;

final class CostCenterPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, CostCenter $costCenter): bool
    {
        return $costCenter->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        return $costCenter->organization_id === TenantContext::id();
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        return $costCenter->organization_id === TenantContext::id();
    }

    public function restore(User $user, CostCenter $costCenter): bool
    {
        return $costCenter->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, CostCenter $costCenter): bool
    {
        return $costCenter->organization_id === TenantContext::id();
    }
}
