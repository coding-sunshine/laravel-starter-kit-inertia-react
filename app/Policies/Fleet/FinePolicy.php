<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Fine;
use App\Models\User;
use App\Services\TenantContext;

final class FinePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Fine $fine): bool
    {
        return $fine->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Fine $fine): bool
    {
        return $fine->organization_id === TenantContext::id();
    }

    public function delete(User $user, Fine $fine): bool
    {
        return $fine->organization_id === TenantContext::id();
    }

    public function restore(User $user, Fine $fine): bool
    {
        return $fine->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, Fine $fine): bool
    {
        return $fine->organization_id === TenantContext::id();
    }
}
