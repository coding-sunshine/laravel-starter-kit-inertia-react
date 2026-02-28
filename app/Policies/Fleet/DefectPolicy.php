<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Defect;
use App\Models\User;
use App\Services\TenantContext;

final class DefectPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Defect $defect): bool
    {
        return $defect->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Defect $defect): bool
    {
        return $defect->organization_id === TenantContext::id();
    }

    public function delete(User $user, Defect $defect): bool
    {
        return $defect->organization_id === TenantContext::id();
    }

    public function restore(User $user, Defect $defect): bool
    {
        return $defect->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Defect $defect): bool
    {
        return $defect->organization_id === TenantContext::id();
    }
}
