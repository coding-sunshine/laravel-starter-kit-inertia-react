<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\PartsInventory;
use App\Models\User;
use App\Services\TenantContext;

final class PartsInventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, PartsInventory $partsInventory): bool
    {
        return $partsInventory->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, PartsInventory $partsInventory): bool
    {
        return $partsInventory->organization_id === TenantContext::id();
    }

    public function delete(User $user, PartsInventory $partsInventory): bool
    {
        return $partsInventory->organization_id === TenantContext::id();
    }
}
