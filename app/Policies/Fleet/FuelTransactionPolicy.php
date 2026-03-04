<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\FuelTransaction;
use App\Models\User;
use App\Services\TenantContext;

final class FuelTransactionPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, FuelTransaction $fuelTransaction): bool
    {
        return $fuelTransaction->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, FuelTransaction $fuelTransaction): bool
    {
        return $fuelTransaction->organization_id === TenantContext::id();
    }

    public function delete(User $user, FuelTransaction $fuelTransaction): bool
    {
        return $fuelTransaction->organization_id === TenantContext::id();
    }
}
