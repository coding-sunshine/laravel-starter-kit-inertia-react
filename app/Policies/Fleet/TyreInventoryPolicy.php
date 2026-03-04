<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\TyreInventory;
use App\Models\User;
use App\Services\TenantContext;

final class TyreInventoryPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, TyreInventory $tyreInventory): bool
    {
        return $tyreInventory->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, TyreInventory $tyreInventory): bool
    {
        return $tyreInventory->organization_id === TenantContext::id();
    }

    public function delete(User $user, TyreInventory $tyreInventory): bool
    {
        return $tyreInventory->organization_id === TenantContext::id();
    }
}
