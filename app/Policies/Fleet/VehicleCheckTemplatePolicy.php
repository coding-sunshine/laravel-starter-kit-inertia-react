<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\VehicleCheckTemplate;
use App\Models\User;
use App\Services\TenantContext;

final class VehicleCheckTemplatePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, VehicleCheckTemplate $vehicleCheckTemplate): bool
    {
        return $vehicleCheckTemplate->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, VehicleCheckTemplate $vehicleCheckTemplate): bool
    {
        return $vehicleCheckTemplate->organization_id === TenantContext::id();
    }

    public function delete(User $user, VehicleCheckTemplate $vehicleCheckTemplate): bool
    {
        return $vehicleCheckTemplate->organization_id === TenantContext::id();
    }
}
