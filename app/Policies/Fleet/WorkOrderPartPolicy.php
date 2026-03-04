<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkOrderPart;
use App\Models\User;
use App\Services\TenantContext;

final class WorkOrderPartPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkOrderPart $workOrderPart): bool
    {
        return $workOrderPart->workOrder->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WorkOrderPart $workOrderPart): bool
    {
        return $workOrderPart->workOrder->organization_id === TenantContext::id();
    }

    public function delete(User $user, WorkOrderPart $workOrderPart): bool
    {
        return $workOrderPart->workOrder->organization_id === TenantContext::id();
    }

    public function restore(User $user, WorkOrderPart $workOrderPart): bool
    {
        return $workOrderPart->workOrder->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, WorkOrderPart $workOrderPart): bool
    {
        return $workOrderPart->workOrder->organization_id === TenantContext::id();
    }
}
