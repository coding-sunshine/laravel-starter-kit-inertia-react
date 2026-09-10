<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

final class AlertPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Alert $alert): bool
    {
        if ($alert->siding_id === null) {
            return $user->isSuperAdmin();
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canAccessSiding($alert->siding_id);
    }

    public function update(User $user, Alert $alert): bool
    {
        return $this->view($user, $alert);
    }
}
