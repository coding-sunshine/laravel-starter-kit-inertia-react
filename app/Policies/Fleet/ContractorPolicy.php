<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Contractor;
use App\Models\User;
use App\Services\TenantContext;

final class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Contractor $contractor): bool
    {
        return $contractor->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $contractor->organization_id === TenantContext::id();
    }

    public function delete(User $user, Contractor $contractor): bool
    {
        return $contractor->organization_id === TenantContext::id();
    }
}
