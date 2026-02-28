<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\EvBatteryData;
use App\Models\User;
use App\Services\TenantContext;

final class EvBatteryDataPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, EvBatteryData $evBatteryData): bool
    {
        $vehicle = $evBatteryData->vehicle;
        return $vehicle && $vehicle->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, EvBatteryData $evBatteryData): bool
    {
        $vehicle = $evBatteryData->vehicle;
        return $vehicle && $vehicle->organization_id === TenantContext::id();
    }

    public function delete(User $user, EvBatteryData $evBatteryData): bool
    {
        $vehicle = $evBatteryData->vehicle;
        return $vehicle && $vehicle->organization_id === TenantContext::id();
    }
}
