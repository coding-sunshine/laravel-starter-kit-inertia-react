<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\GeofenceEvent;
use App\Models\User;
use App\Services\TenantContext;

final class GeofenceEventPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, GeofenceEvent $event): bool
    {
        return $event->organization_id === TenantContext::id();
    }
}
