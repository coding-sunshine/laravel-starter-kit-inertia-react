<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\CarbonTarget;
use App\Models\User;
use App\Services\TenantContext;

final class CarbonTargetPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, CarbonTarget $target): bool
    {
        return $target->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, CarbonTarget $target): bool
    {
        return $target->organization_id === TenantContext::id();
    }

    public function delete(User $user, CarbonTarget $target): bool
    {
        return $target->organization_id === TenantContext::id();
    }
}
