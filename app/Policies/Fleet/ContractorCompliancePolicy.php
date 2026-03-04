<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ContractorCompliance;
use App\Models\User;
use App\Services\TenantContext;

final class ContractorCompliancePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ContractorCompliance $contractorCompliance): bool
    {
        return $contractorCompliance->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ContractorCompliance $contractorCompliance): bool
    {
        return $contractorCompliance->organization_id === TenantContext::id();
    }

    public function delete(User $user, ContractorCompliance $contractorCompliance): bool
    {
        return $contractorCompliance->organization_id === TenantContext::id();
    }
}
