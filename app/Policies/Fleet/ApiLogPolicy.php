<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ApiLog;
use App\Models\User;
use App\Services\TenantContext;

final class ApiLogPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ApiLog $apiLog): bool
    {
        return $apiLog->organization_id === TenantContext::id();
    }
}
