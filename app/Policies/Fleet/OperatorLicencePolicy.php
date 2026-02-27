<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\OperatorLicence;
use App\Models\User;
use App\Services\TenantContext;

final class OperatorLicencePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, OperatorLicence $operatorLicence): bool
    {
        return $operatorLicence->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, OperatorLicence $operatorLicence): bool
    {
        return $operatorLicence->organization_id === TenantContext::id();
    }

    public function delete(User $user, OperatorLicence $operatorLicence): bool
    {
        return $operatorLicence->organization_id === TenantContext::id();
    }

    public function restore(User $user, OperatorLicence $operatorLicence): bool
    {
        return $operatorLicence->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, OperatorLicence $operatorLicence): bool
    {
        return $operatorLicence->organization_id === TenantContext::id();
    }
}
