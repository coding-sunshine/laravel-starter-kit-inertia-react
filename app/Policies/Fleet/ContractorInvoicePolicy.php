<?php

declare(strict_types=1);

namespace App\Policies\Fleet;

use App\Models\Fleet\ContractorInvoice;
use App\Models\User;
use App\Services\TenantContext;

final class ContractorInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::check();
    }

    public function view(User $user, ContractorInvoice $contractorInvoice): bool
    {
        return $contractorInvoice->organization_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return TenantContext::check();
    }

    public function update(User $user, ContractorInvoice $contractorInvoice): bool
    {
        return $contractorInvoice->organization_id === TenantContext::id();
    }

    public function delete(User $user, ContractorInvoice $contractorInvoice): bool
    {
        return $contractorInvoice->organization_id === TenantContext::id();
    }
}
