<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\SafetyObservation;
use App\Models\User;
use App\Services\TenantContext;

final class SafetyObservationPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, SafetyObservation $safetyObservation): bool
    {
        return $safetyObservation->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, SafetyObservation $safetyObservation): bool
    {
        return $safetyObservation->organization_id === TenantContext::id();
    }

    public function delete(User $user, SafetyObservation $safetyObservation): bool
    {
        return $safetyObservation->organization_id === TenantContext::id();
    }
}
