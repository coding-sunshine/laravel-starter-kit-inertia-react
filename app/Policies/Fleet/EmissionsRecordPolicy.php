<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\EmissionsRecord;
use App\Models\User;
use App\Services\TenantContext;

final class EmissionsRecordPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, EmissionsRecord $record): bool
    {
        return $record->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, EmissionsRecord $record): bool
    {
        return $record->organization_id === TenantContext::id();
    }

    public function delete(User $user, EmissionsRecord $record): bool
    {
        return $record->organization_id === TenantContext::id();
    }
}
