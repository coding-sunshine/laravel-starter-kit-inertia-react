<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ElockEvent;
use App\Models\User;
use App\Services\TenantContext;

final class ElockEventPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ElockEvent $elockEvent): bool
    {
        return $elockEvent->organization_id === TenantContext::id();
    }
}
