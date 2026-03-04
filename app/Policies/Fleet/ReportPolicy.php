<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\Report;
use App\Models\User;
use App\Services\TenantContext;

final class ReportPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, Report $report): bool
    {
        return $report->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, Report $report): bool
    {
        return $report->organization_id === TenantContext::id();
    }

    public function delete(User $user, Report $report): bool
    {
        return $report->organization_id === TenantContext::id();
    }
}
