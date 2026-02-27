<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Trailer;
use App\Models\User;
use App\Services\TenantContext;

final class TrailerPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Trailer $trailer): bool
    {
        return $trailer->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Trailer $trailer): bool
    {
        return $trailer->organization_id === TenantContext::id();
    }

    public function delete(User $user, Trailer $trailer): bool
    {
        return $trailer->organization_id === TenantContext::id();
    }

    public function restore(User $user, Trailer $trailer): bool
    {
        return $trailer->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Trailer $trailer): bool
    {
        return $trailer->organization_id === TenantContext::id();
    }
}
