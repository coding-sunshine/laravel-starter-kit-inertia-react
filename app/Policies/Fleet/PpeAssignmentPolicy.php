<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\PpeAssignment;
use App\Models\User;
use App\Services\TenantContext;

final class PpeAssignmentPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, PpeAssignment $ppeAssignment): bool
    {
        return $ppeAssignment->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, PpeAssignment $ppeAssignment): bool
    {
        return $ppeAssignment->organization_id === TenantContext::id();
    }

    public function delete(User $user, PpeAssignment $ppeAssignment): bool
    {
        return $ppeAssignment->organization_id === TenantContext::id();
    }
}
