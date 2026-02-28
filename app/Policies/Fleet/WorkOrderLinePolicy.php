<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\WorkOrderLine;
use App\Models\User;
use App\Services\TenantContext;

final class WorkOrderLinePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, WorkOrderLine $workOrderLine): bool
    {
        return $workOrderLine->workOrder->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, WorkOrderLine $workOrderLine): bool
    {
        return $workOrderLine->workOrder->organization_id === TenantContext::id();
    }

    public function delete(User $user, WorkOrderLine $workOrderLine): bool
    {
        return $workOrderLine->workOrder->organization_id === TenantContext::id();
    }

    public function restore(User $user, WorkOrderLine $workOrderLine): bool
    {
        return $workOrderLine->workOrder->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, WorkOrderLine $workOrderLine): bool
    {
        return $workOrderLine->workOrder->organization_id === TenantContext::id();
    }
}
