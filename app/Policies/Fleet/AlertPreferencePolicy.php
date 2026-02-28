<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\AlertPreference;
use App\Models\User;
use App\Services\TenantContext;

final class AlertPreferencePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, AlertPreference $alertPreference): bool
    {
        return $alertPreference->user_id === $user->id
            && $alertPreference->organization_id === TenantContext::id();
    }

    public function update(User $user, AlertPreference $alertPreference): bool
    {
        return $alertPreference->user_id === $user->id
            && $alertPreference->organization_id === TenantContext::id();
    }
}
