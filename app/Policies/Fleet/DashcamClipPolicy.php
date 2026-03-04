<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DashcamClip;
use App\Models\User;
use App\Services\TenantContext;

final class DashcamClipPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DashcamClip $dashcamClip): bool
    {
        return $dashcamClip->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, DashcamClip $dashcamClip): bool
    {
        return $dashcamClip->organization_id === TenantContext::id();
    }

    public function delete(User $user, DashcamClip $dashcamClip): bool
    {
        return $dashcamClip->organization_id === TenantContext::id();
    }
}
