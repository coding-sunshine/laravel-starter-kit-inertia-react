<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ensures Filament resource queries are scoped to the current tenant (organization).
 *
 * Use on any Filament Resource whose model has an organization_id column.
 * getEloquentQuery() will filter by tenant_id() unless the user is a super-admin.
 * Override static::disableTenantScoping() to return true to skip scoping (e.g. for
 * global resources like User or Organization).
 */
trait ScopesToCurrentTenant
{
    /**
     * Return true to disable tenant scoping for this resource (e.g. super-admin view-all).
     */
    public static function disableTenantScoping(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Apply tenant scope to the query.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function applyTenantScope(Builder $query): Builder
    {
        if (static::disableTenantScoping()) {
            return $query;
        }

        $tenantId = tenant_id();
        if ($tenantId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('organization_id', $tenantId);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyTenantScope(parent::getEloquentQuery());
    }
}
