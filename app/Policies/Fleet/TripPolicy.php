<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Trip;
use App\Models\User;
use App\Services\TenantContext;

final class TripPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Trip $trip): bool
    {
        return $trip->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Trip $trip): bool
    {
        return $trip->organization_id === TenantContext::id();
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $trip->organization_id === TenantContext::id();
    }
}
