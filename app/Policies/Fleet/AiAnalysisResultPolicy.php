<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\User;
use App\Services\TenantContext;

final class AiAnalysisResultPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, AiAnalysisResult $result): bool
    {
        return $result->organization_id === TenantContext::id();
    }
}
