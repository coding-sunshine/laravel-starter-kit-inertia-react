<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\FuelCard;
use App\Models\User;
use App\Services\TenantContext;

final class FuelCardPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, FuelCard $fuelCard): bool
    {
        return $fuelCard->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, FuelCard $fuelCard): bool
    {
        return $fuelCard->organization_id === TenantContext::id();
    }

    public function delete(User $user, FuelCard $fuelCard): bool
    {
        return $fuelCard->organization_id === TenantContext::id();
    }

    public function restore(User $user, FuelCard $fuelCard): bool
    {
        return $fuelCard->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, FuelCard $fuelCard): bool
    {
        return $fuelCard->organization_id === TenantContext::id();
    }
}
