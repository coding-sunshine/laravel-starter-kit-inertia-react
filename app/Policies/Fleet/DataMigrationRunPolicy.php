<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\DataMigrationRun;
use App\Models\User;
use App\Services\TenantContext;

final class DataMigrationRunPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, DataMigrationRun $dataMigrationRun): bool
    {
        if ($dataMigrationRun->organization_id === null) {
            return $user->can('access admin panel');
        }

        return $dataMigrationRun->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check() && $user->can('access admin panel');
    }
}
