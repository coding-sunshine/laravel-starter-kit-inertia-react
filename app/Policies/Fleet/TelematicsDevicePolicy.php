<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\TelematicsDevice;
use App\Models\User;
use App\Services\TenantContext;

final class TelematicsDevicePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, TelematicsDevice $device): bool
    {
        return $device->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, TelematicsDevice $device): bool
    {
        return $device->organization_id === TenantContext::id();
    }

    public function delete(User $user, TelematicsDevice $device): bool
    {
        return $device->organization_id === TenantContext::id();
    }
}
