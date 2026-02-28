<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Incident;
use App\Models\User;
use App\Services\TenantContext;

final class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Incident $incident): bool
    {
        return $incident->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Incident $incident): bool
    {
        return $incident->organization_id === TenantContext::id();
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $incident->organization_id === TenantContext::id();
    }

    public function restore(User $user, Incident $incident): bool
    {
        return $incident->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Incident $incident): bool
    {
        return $incident->organization_id === TenantContext::id();
    }
}
