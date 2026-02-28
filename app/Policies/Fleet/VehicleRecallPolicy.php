<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleRecall;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleRecallPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleRecall $vehicleRecall): bool
    {
        return $vehicleRecall->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleRecall $vehicleRecall): bool
    {
        return $vehicleRecall->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleRecall $vehicleRecall): bool
    {
        return $vehicleRecall->organization_id === TenantContext::id();
    }

    public function restore(User $user, VehicleRecall $vehicleRecall): bool
    {
        return $vehicleRecall->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, VehicleRecall $vehicleRecall): bool
    {
        return $vehicleRecall->organization_id === TenantContext::id();
    }
}
