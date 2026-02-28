<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\PartsSupplier;
use App\Models\User;
use App\Services\TenantContext;

final class PartsSupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, PartsSupplier $partsSupplier): bool
    {
        return $partsSupplier->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, PartsSupplier $partsSupplier): bool
    {
        return $partsSupplier->organization_id === TenantContext::id();
    }

    public function delete(User $user, PartsSupplier $partsSupplier): bool
    {
        return $partsSupplier->organization_id === TenantContext::id();
    }
}
