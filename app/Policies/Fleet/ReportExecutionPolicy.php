<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ReportExecution;
use App\Models\User;
use App\Services\TenantContext;

final class ReportExecutionPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ReportExecution $reportExecution): bool
    {
        return $reportExecution->organization_id === TenantContext::id();
    }
}
