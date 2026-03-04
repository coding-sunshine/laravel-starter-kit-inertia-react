<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DriverWellnessRecord;
use App\Models\User;
use App\Services\TenantContext;

final class DriverWellnessRecordPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DriverWellnessRecord $driverWellnessRecord): bool
    {
        return $driverWellnessRecord->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, DriverWellnessRecord $driverWellnessRecord): bool
    {
        return $driverWellnessRecord->organization_id === TenantContext::id();
    }

    public function delete(User $user, DriverWellnessRecord $driverWellnessRecord): bool
    {
        return $driverWellnessRecord->organization_id === TenantContext::id();
    }
}
