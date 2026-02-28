<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkflowDefinition;
use App\Models\User;
use App\Services\TenantContext;

final class WorkflowDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkflowDefinition $def): bool
    {
        return $def->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WorkflowDefinition $def): bool
    {
        return $def->organization_id === TenantContext::id();
    }

    public function delete(User $user, WorkflowDefinition $def): bool
    {
        return $def->organization_id === TenantContext::id();
    }
}
