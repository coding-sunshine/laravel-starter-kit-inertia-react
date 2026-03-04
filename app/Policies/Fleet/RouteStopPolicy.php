<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\RouteStop;
use App\Models\User;
use App\Services\TenantContext;

final class RouteStopPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, RouteStop $routeStop): bool
    {
        return $routeStop->route?->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, RouteStop $routeStop): bool
    {
        return $routeStop->route?->organization_id === TenantContext::id();
    }

    public function delete(User $user, RouteStop $routeStop): bool
    {
        return $routeStop->route?->organization_id === TenantContext::id();
    }
}
