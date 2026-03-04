<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ServiceSchedule;
use App\Models\User;
use App\Services\TenantContext;

final class ServiceSchedulePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $serviceSchedule->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $serviceSchedule->organization_id === TenantContext::id();
    }

    public function delete(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $serviceSchedule->organization_id === TenantContext::id();
    }

    public function restore(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $serviceSchedule->organization_id === TenantContext::id();
    }

    public function forceDelete(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $serviceSchedule->organization_id === TenantContext::id();
    }
}
