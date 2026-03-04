<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\TrainingCourse;
use App\Models\User;
use App\Services\TenantContext;

final class TrainingCoursePolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, TrainingCourse $trainingCourse): bool
    {
        return $trainingCourse->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, TrainingCourse $trainingCourse): bool
    {
        return $trainingCourse->organization_id === TenantContext::id();
    }

    public function delete(User $user, TrainingCourse $trainingCourse): bool
    {
        return $trainingCourse->organization_id === TenantContext::id();
    }
}
