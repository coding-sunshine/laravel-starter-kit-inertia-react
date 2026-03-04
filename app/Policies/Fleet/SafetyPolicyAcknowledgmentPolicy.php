<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\SafetyPolicyAcknowledgment;
use App\Models\User;
use App\Services\TenantContext;

final class SafetyPolicyAcknowledgmentPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, SafetyPolicyAcknowledgment $safetyPolicyAcknowledgment): bool
    {
        return $safetyPolicyAcknowledgment->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, SafetyPolicyAcknowledgment $safetyPolicyAcknowledgment): bool
    {
        return $safetyPolicyAcknowledgment->organization_id === TenantContext::id();
    }

    public function delete(User $user, SafetyPolicyAcknowledgment $safetyPolicyAcknowledgment): bool
    {
        return $safetyPolicyAcknowledgment->organization_id === TenantContext::id();
    }
}
