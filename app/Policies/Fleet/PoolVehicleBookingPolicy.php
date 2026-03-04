<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\PoolVehicleBooking;
use App\Models\User;
use App\Services\TenantContext;

final class PoolVehicleBookingPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, PoolVehicleBooking $poolVehicleBooking): bool
    {
        return $poolVehicleBooking->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, PoolVehicleBooking $poolVehicleBooking): bool
    {
        return $poolVehicleBooking->organization_id === TenantContext::id();
    }

    public function delete(User $user, PoolVehicleBooking $poolVehicleBooking): bool
    {
        return $poolVehicleBooking->organization_id === TenantContext::id();
    }
}
