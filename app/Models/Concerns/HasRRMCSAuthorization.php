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
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is a management user (can view cross-org reports)
     */
    public function isManagement(): bool
    {
        return $this->hasRole('management') || $this->isSuperAdmin();
    }

    /**
     * Check if user is finance (can view billing/penalty reports)
     */
    public function isFinance(): bool
    {
        return $this->hasRole('finance') || $this->isSuperAdmin();
    }

    /**
     * Check if user is a siding in charge (can approve rakes, manage indents)
     */
    public function isSidingInCharge(): bool
    {
        return $this->hasRole('siding_in_charge') || $this->isSuperAdmin();
    }

    /**
     * Check if user is a siding operator (can create rakes, load wagons)
     */
    public function isSidingOperator(): bool
    {
        return $this->hasRole('siding_operator') || $this->isSidingInCharge();
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
                    'siding_user',
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

        // Check if user has explicit access to siding
        return $this->sidings()
            ->where('siding_id', $sidingId)
            ->exists();
    }

    /**
     * Get all sidings assigned to this user
     *
     * @return BelongsToMany<Siding, $this>
     */
    public function sidings(): BelongsToMany
    {
        return $this->belongsToMany(Siding::class, 'siding_user')
            ->withPivot(['assigned_at', 'is_primary'])
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
                'assigned_at' => now(),
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
        return $this->sidings()
            ->orderByPivot('assigned_at', 'desc')
            ->first();
    }
}
