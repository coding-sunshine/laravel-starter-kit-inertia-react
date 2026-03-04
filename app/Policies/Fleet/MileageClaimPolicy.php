<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\MileageClaim;
use App\Models\User;
use App\Services\TenantContext;

final class MileageClaimPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, MileageClaim $mileageClaim): bool
    {
        return $mileageClaim->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, MileageClaim $mileageClaim): bool
    {
        return $mileageClaim->organization_id === TenantContext::id();
    }

    public function delete(User $user, MileageClaim $mileageClaim): bool
    {
        return $mileageClaim->organization_id === TenantContext::id();
    }
}
