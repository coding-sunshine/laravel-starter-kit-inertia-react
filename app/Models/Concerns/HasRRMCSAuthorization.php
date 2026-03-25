<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Siding;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * RRMCS-specific authorization methods for User model
 *
 * Provides convenience methods for checking RRMCS role-based access
 * and siding-level permissions.
 */
trait HasRRMCSAuthorization
{
    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->hasRole('super-admin');
    }

    /**
     * Check if user is a management user (can view cross-org reports)
     */
    public function isManagement(): bool
    {
        if ($this->hasRole('management')) {
            return true;
        }

        return (bool) $this->isSuperAdmin();
    }

    /**
     * Check if user is finance (can view billing/penalty reports)
     */
    public function isFinance(): bool
    {
        if ($this->hasRole('finance')) {
            return true;
        }

        return (bool) $this->isSuperAdmin();
    }

    /**
     * Check if user is a siding in charge (can approve rakes, manage indents)
     */
    public function isSidingInCharge(): bool
    {
        if ($this->hasRole('siding_in_charge')) {
            return true;
        }

        return (bool) $this->isSuperAdmin();
    }

    /**
     * Check if user is a siding operator (can create rakes, load wagons)
     */
    public function isSidingOperator(): bool
    {
        if ($this->hasRole('siding_operator')) {
            return true;
        }

        return (bool) $this->isSidingInCharge();
    }

    /**
     * Check if user belongs to an organization
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    /**
     * Get all sidings the user can access
     */
    public function accessibleSidings(): BelongsToMany
    {
        // Super admin can access all sidings
        if ($this->isSuperAdmin()) {
            return Siding::query()
                ->getQuery()
                ->getModel()
                ->newBelongsToMany(
                    Siding::class,
                    'user_siding',
                    'user_id',
                    'siding_id'
                );
        }

        // Other users can only access sidings they're explicitly assigned to
        return $this->sidings();
    }

    /**
     * Check if user can access a specific siding
     */
    public function canAccessSiding(int $sidingId): bool
    {
        // Super admin can access any siding
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user has explicit access to siding (pivot table)
        if ($this->sidings()
            ->where('siding_id', $sidingId)
            ->exists()) {
            return true;
        }

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (property_exists($this, 'siding_id') && $this->siding_id !== null) {
            return (int) $this->siding_id === $sidingId;
        }

        return false;
    }

    /**
     * Get all sidings assigned to this user
     *
     * @return BelongsToMany<Siding, $this>
     */
    public function sidings(): BelongsToMany
    {
        return $this->belongsToMany(Siding::class, 'user_siding')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    /**
     * Assign user to a siding
     */
    public function assignToSiding(Siding|int $siding): void
    {
        $sidingId = $siding instanceof Siding ? $siding->id : $siding;

        if (! $this->sidings()->where('siding_id', $sidingId)->exists()) {
            $this->sidings()->attach($sidingId, [
                'is_primary' => false,
            ]);
        }
    }

    /**
     * Remove user from a siding
     */
    public function removeFromSiding(Siding|int $siding): void
    {
        $sidingId = $siding instanceof Siding ? $siding->id : $siding;
        $this->sidings()->detach($sidingId);
    }

    /**
     * Get the user's primary siding (most recently assigned)
     */
    public function getPrimarySiding(): ?Siding
    {
        if (property_exists($this, 'siding_id') && $this->siding_id !== null) {
            return Siding::query()->find($this->siding_id);
        }

        return $this->sidings()
            ->orderByPivot('is_primary', 'desc')
            ->orderByPivot('updated_at', 'desc')
            ->first();
    }
}
