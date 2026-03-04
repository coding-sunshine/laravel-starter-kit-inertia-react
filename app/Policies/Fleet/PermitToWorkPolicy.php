<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\PermitToWork;
use App\Models\User;
use App\Services\TenantContext;

final class PermitToWorkPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, PermitToWork $permitToWork): bool
    {
        return $permitToWork->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, PermitToWork $permitToWork): bool
    {
        return $permitToWork->organization_id === TenantContext::id();
    }

    public function delete(User $user, PermitToWork $permitToWork): bool
    {
        return $permitToWork->organization_id === TenantContext::id();
    }
}
