<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ToolboxTalk;
use App\Models\User;
use App\Services\TenantContext;

final class ToolboxTalkPolicy
{
    public function viewAny(): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ToolboxTalk $toolboxTalk): bool
    {
        return $toolboxTalk->organization_id === TenantContext::id();
    }

    public function create(): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ToolboxTalk $toolboxTalk): bool
    {
        return $toolboxTalk->organization_id === TenantContext::id();
    }

    public function delete(User $user, ToolboxTalk $toolboxTalk): bool
    {
        return $toolboxTalk->organization_id === TenantContext::id();
    }
}
