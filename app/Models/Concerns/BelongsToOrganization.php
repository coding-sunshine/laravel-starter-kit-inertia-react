<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public function initializeBelongsToOrganization(): void
    {
        if (property_exists($this, 'guarded') && is_array($this->guarded) && $this->guarded !== ['*']
            && ! in_array('organization_id', $this->guarded, true)) {
            $this->guarded[] = 'organization_id';
        }
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function belongsToOrganization(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        return $this->organization_id === $organizationId;
    }

    public function belongsToCurrentOrganization(): bool
    {
        return $this->organization_id === TenantContext::id();
    }

    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model): void {
            if (empty($model->organization_id)) {
                $model->organization_id = TenantContext::id();
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeForOrganization(Builder $query, Organization|int $organization): Builder
    {
        $organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        return $query->where($this->getTable().'.organization_id', $organizationId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class);
    }
}
