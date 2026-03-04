<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkflowExecution;
use App\Models\User;
use App\Services\TenantContext;

final class WorkflowExecutionPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkflowExecution $execution): bool
    {
        return $execution->workflowDefinition->organization_id === TenantContext::id();
    }
}
