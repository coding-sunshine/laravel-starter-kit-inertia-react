<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkOrder;
use App\Models\User;
use App\Services\TenantContext;

final class WorkOrderPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $workOrder->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $workOrder->organization_id === TenantContext::id();
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $workOrder->organization_id === TenantContext::id();
    }

    public function restore(User $user, WorkOrder $workOrder): bool
    {
        return $workOrder->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, WorkOrder $workOrder): bool
    {
        return $workOrder->organization_id === TenantContext::id();
    }
}
