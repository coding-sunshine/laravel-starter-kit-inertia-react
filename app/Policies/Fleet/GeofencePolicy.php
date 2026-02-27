<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Geofence;
use App\Models\User;
use App\Services\TenantContext;

final class GeofencePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Geofence $geofence): bool
    {
        return $geofence->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Geofence $geofence): bool
    {
        return $geofence->organization_id === TenantContext::id();
    }

    public function delete(User $user, Geofence $geofence): bool
    {
        return $geofence->organization_id === TenantContext::id();
    }

    public function restore(User $user, Geofence $geofence): bool
    {
        return $geofence->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Geofence $geofence): bool
    {
        return $geofence->organization_id === TenantContext::id();
    }
}
