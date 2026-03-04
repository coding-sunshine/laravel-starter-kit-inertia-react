<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkshopBay;
use App\Models\User;
use App\Services\TenantContext;

final class WorkshopBayPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkshopBay $workshopBay): bool
    {
        return $workshopBay->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WorkshopBay $workshopBay): bool
    {
        return $workshopBay->organization_id === TenantContext::id();
    }

    public function delete(User $user, WorkshopBay $workshopBay): bool
    {
        return $workshopBay->organization_id === TenantContext::id();
    }
}
