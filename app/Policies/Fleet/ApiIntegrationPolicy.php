<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ApiIntegration;
use App\Models\User;
use App\Services\TenantContext;

final class ApiIntegrationPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ApiIntegration $apiIntegration): bool
    {
        return $apiIntegration->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ApiIntegration $apiIntegration): bool
    {
        return $apiIntegration->organization_id === TenantContext::id();
    }

    public function delete(User $user, ApiIntegration $apiIntegration): bool
    {
        return $apiIntegration->organization_id === TenantContext::id();
    }
}
