<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ComplianceItem;
use App\Models\User;
use App\Services\TenantContext;

final class ComplianceItemPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ComplianceItem $complianceItem): bool
    {
        return $complianceItem->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ComplianceItem $complianceItem): bool
    {
        return $complianceItem->organization_id === TenantContext::id();
    }

    public function delete(User $user, ComplianceItem $complianceItem): bool
    {
        return $complianceItem->organization_id === TenantContext::id();
    }

    public function restore(User $user, ComplianceItem $complianceItem): bool
    {
        return $complianceItem->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, ComplianceItem $complianceItem): bool
    {
        return $complianceItem->organization_id === TenantContext::id();
    }
}
