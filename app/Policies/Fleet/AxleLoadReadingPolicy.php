<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\AxleLoadReading;
use App\Models\User;
use App\Services\TenantContext;

final class AxleLoadReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, AxleLoadReading $axleLoadReading): bool
    {
        return $axleLoadReading->organization_id === TenantContext::id();
    }
}
