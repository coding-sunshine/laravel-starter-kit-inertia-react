<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\AiJobRun;
use App\Models\User;
use App\Services\TenantContext;

final class AiJobRunPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, AiJobRun $job): bool
    {
        return $job->organization_id === TenantContext::id();
    }
}
