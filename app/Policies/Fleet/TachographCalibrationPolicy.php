<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\TachographCalibration;
use App\Models\User;
use App\Services\TenantContext;

final class TachographCalibrationPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, TachographCalibration $tachographCalibration): bool
    {
        return $tachographCalibration->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, TachographCalibration $tachographCalibration): bool
    {
        return $tachographCalibration->organization_id === TenantContext::id();
    }

    public function delete(User $user, TachographCalibration $tachographCalibration): bool
    {
        return $tachographCalibration->organization_id === TenantContext::id();
    }
}
