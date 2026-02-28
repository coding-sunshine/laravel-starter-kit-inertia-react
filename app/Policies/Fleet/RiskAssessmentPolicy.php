<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\RiskAssessment;
use App\Models\User;
use App\Services\TenantContext;

final class RiskAssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, RiskAssessment $riskAssessment): bool
    {
        return $riskAssessment->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, RiskAssessment $riskAssessment): bool
    {
        return $riskAssessment->organization_id === TenantContext::id();
    }

    public function delete(User $user, RiskAssessment $riskAssessment): bool
    {
        return $riskAssessment->organization_id === TenantContext::id();
    }
}
