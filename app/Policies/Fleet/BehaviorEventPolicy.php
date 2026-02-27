<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\BehaviorEvent;
use App\Models\User;
use App\Services\TenantContext;

final class BehaviorEventPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, BehaviorEvent $event): bool
    {
        return $event->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, BehaviorEvent $event): bool
    {
        return $event->organization_id === TenantContext::id();
    }

    public function delete(User $user, BehaviorEvent $event): bool
    {
        return $event->organization_id === TenantContext::id();
    }
}
