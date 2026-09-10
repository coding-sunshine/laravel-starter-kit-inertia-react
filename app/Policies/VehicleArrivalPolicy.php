<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleArrival;

final class VehicleArrivalPolicy
{
    public function view(User $user, VehicleArrival $arrival): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canAccessSiding($arrival->siding_id);
    }
}
