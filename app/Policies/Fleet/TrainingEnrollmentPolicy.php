<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\TrainingEnrollment;
use App\Models\User;
use App\Services\TenantContext;

final class TrainingEnrollmentPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, TrainingEnrollment $trainingEnrollment): bool
    {
        return $trainingEnrollment->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, TrainingEnrollment $trainingEnrollment): bool
    {
        return $trainingEnrollment->organization_id === TenantContext::id();
    }

    public function delete(User $user, TrainingEnrollment $trainingEnrollment): bool
    {
        return $trainingEnrollment->organization_id === TenantContext::id();
    }
}
