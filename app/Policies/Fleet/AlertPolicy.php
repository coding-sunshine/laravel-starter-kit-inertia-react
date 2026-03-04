<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Alert;
use App\Models\User;
use App\Services\TenantContext;

final class AlertPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Alert $alert): bool
    {
        return $alert->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Alert $alert): bool
    {
        return $alert->organization_id === TenantContext::id();
    }

    public function delete(User $user, Alert $alert): bool
    {
        return $alert->organization_id === TenantContext::id();
    }
}
